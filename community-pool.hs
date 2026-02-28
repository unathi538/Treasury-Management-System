{-# LANGUAGE DataKinds #-}
{-# LANGUAGE NoImplicitPrelude #-}
{-# LANGUAGE TemplateHaskell #-}
{-# LANGUAGE ScopedTypeVariables #-}
{-# LANGUAGE OverloadedStrings #-}
{-# LANGUAGE TypeApplications #-}

module Main where

import Prelude (IO, String, FilePath, putStrLn, (<>), take)
import qualified Prelude as P
import qualified Data.Text as T

import Plutus.V2.Ledger.Api
import Plutus.V2.Ledger.Contexts
import qualified Plutus.V2.Ledger.Api as PlutusV2
import PlutusTx
import PlutusTx.Prelude hiding (Semigroup(..), unless)

import qualified Codec.Serialise as Serialise
import qualified Data.ByteString.Lazy  as LBS
import qualified Data.ByteString.Short as SBS
import qualified Data.ByteString       as BS
import qualified Data.ByteString.Base16 as B16

import qualified Cardano.Api as C
import qualified Cardano.Api.Shelley as CS

------------------------------------------------------------------------
-- Datum & Redeemer
------------------------------------------------------------------------

data PoolDatum = PoolDatum
  { treasurer :: PubKeyHash
  }
PlutusTx.unstableMakeIsData ''PoolDatum

data PoolAction
  = Deposit
  | Withdraw
PlutusTx.unstableMakeIsData ''PoolAction

------------------------------------------------------------------------
-- Helpers
------------------------------------------------------------------------

{-# INLINABLE signedBy #-}
signedBy :: PubKeyHash -> ScriptContext -> Bool
signedBy pkh ctx =
  txSignedBy (scriptContextTxInfo ctx) pkh

{-# INLINABLE ownInput #-}
ownInput :: ScriptContext -> TxOut
ownInput ctx =
  case findOwnInput ctx of
    Nothing -> traceError "missing script input"
    Just i  -> txInInfoResolved i

{-# INLINABLE continuingOutputs #-}
continuingOutputs :: ScriptContext -> [TxOut]
continuingOutputs ctx =
  getContinuingOutputs ctx

{-# INLINABLE datumOf #-}
datumOf :: TxInfo -> TxOut -> PoolDatum
datumOf info o =
  case txOutDatum o of
    NoOutputDatum ->
      traceError "no datum on output"
    OutputDatum (Datum d) ->
      unsafeFromBuiltinData d
    OutputDatumHash dh ->
      case findDatum dh info of
        Nothing        -> traceError "datum hash not found"
        Just (Datum d) -> unsafeFromBuiltinData d

{-# INLINABLE valueGE #-}
valueGE :: Value -> Value -> Bool
valueGE a b =
  -- True if a >= b for each asset (i.e. b is "contained" in a)
  isZero (b - a)

------------------------------------------------------------------------
-- Validator Logic (single-UTxO friendly + safe)
------------------------------------------------------------------------

{-# INLINABLE mkPoolValidator #-}
mkPoolValidator :: PoolDatum -> PoolAction -> ScriptContext -> Bool
mkPoolValidator dat action ctx =
  case action of

    ------------------------------------------------------------
    -- Deposit:
    --  - must keep exactly ONE continuing output at the script
    --  - must preserve the treasurer in datum
    --  - must not reduce the script value (so deposits cannot steal)
    ------------------------------------------------------------
    Deposit ->
      let info    = scriptContextTxInfo ctx
          inTxOut = ownInput ctx
          outs    = continuingOutputs ctx
      in  traceIfFalse "expected exactly 1 continuing output" (length outs == 1) &&
          let outTxOut   = head outs
              outDatum   = datumOf info outTxOut
              inVal      = txOutValue inTxOut
              outVal     = txOutValue outTxOut
          in  traceIfFalse "treasurer changed" (treasurer outDatum == treasurer dat) &&
              traceIfFalse "script value decreased" (outVal `valueGE` inVal)

    ------------------------------------------------------------
    -- Withdraw:
    --  - treasurer must sign
    --  - if pool continues, must be exactly ONE continuing output
    --  - continuing output must preserve treasurer in datum
    --  (we do NOT enforce exact withdrawal amount on-chain here;
    --   treasurer is trusted to choose amount off-chain)
    ------------------------------------------------------------
    Withdraw ->
      let info = scriptContextTxInfo ctx
          outs = continuingOutputs ctx
          okContinue =
            if length outs == 0
              then True -- allow fully emptying/closing pool
              else
                traceIfFalse "expected 0 or 1 continuing output" (length outs == 1) &&
                let outTxOut = head outs
                    outDatum = datumOf info outTxOut
                in traceIfFalse "treasurer changed" (treasurer outDatum == treasurer dat)
      in  traceIfFalse "treasurer signature missing" (signedBy (treasurer dat) ctx) &&
          okContinue

------------------------------------------------------------------------
-- Untyped Wrapper
------------------------------------------------------------------------

{-# INLINABLE mkValidatorUntyped #-}
mkValidatorUntyped :: BuiltinData -> BuiltinData -> BuiltinData -> ()
mkValidatorUntyped d r c =
  if mkPoolValidator
      (unsafeFromBuiltinData d)
      (unsafeFromBuiltinData r)
      (unsafeFromBuiltinData c)
  then ()
  else error ()

validator :: Validator
validator =
  mkValidatorScript $$(PlutusTx.compile [|| mkValidatorUntyped ||])

------------------------------------------------------------------------
-- Validator Hash & Script Address
------------------------------------------------------------------------

plutusValidatorHash :: PlutusV2.Validator -> PlutusV2.ValidatorHash
plutusValidatorHash val =
  let bytes = Serialise.serialise val
      short = SBS.toShort (LBS.toStrict bytes)
  in PlutusV2.ValidatorHash (toBuiltin (SBS.fromShort short))

plutusScriptAddress :: Address
plutusScriptAddress =
  Address
    (ScriptCredential (plutusValidatorHash validator))
    Nothing

------------------------------------------------------------------------
-- Bech32 Script Address (Off-chain)
------------------------------------------------------------------------

toBech32ScriptAddress :: C.NetworkId -> Validator -> String
toBech32ScriptAddress network val =
  let serialised = SBS.toShort . LBS.toStrict $ Serialise.serialise val
      plutusScript :: C.PlutusScript C.PlutusScriptV2
      plutusScript = CS.PlutusScriptSerialised serialised
      scriptHash   = C.hashScript (C.PlutusScript C.PlutusScriptV2 plutusScript)
      shelleyAddr :: C.AddressInEra C.BabbageEra
      shelleyAddr =
        C.makeShelleyAddressInEra
          network
          (C.PaymentCredentialByScript scriptHash)
          C.NoStakeAddress
      -- (kept exactly as you asked in previous preference)
      shelleyAddr2 :: C.AddressInEra C.BabbageEra
      shelleyAddr2 = shelleyAddr
  in T.unpack (C.serialiseAddress shelleyAddr2)

------------------------------------------------------------------------
-- CBOR HEX
------------------------------------------------------------------------

validatorToCBORHex :: Validator -> String
validatorToCBORHex val =
  let bytes = LBS.toStrict $ Serialise.serialise val
  in BS.foldr (\b acc -> byteToHex b <> acc) "" bytes
 where
  hexChars = "0123456789abcdef"
  byteToHex b =
    let hi = P.fromIntegral b `P.div` 16
        lo = P.fromIntegral b `P.mod` 16
    in [ hexChars P.!! hi, hexChars P.!! lo ]

------------------------------------------------------------------------
-- File Writer
------------------------------------------------------------------------

writeValidator :: FilePath -> Validator -> IO ()
writeValidator path val = do
  LBS.writeFile path (Serialise.serialise val)
  putStrLn $ "Validator written to: " <> path

writeCBOR :: FilePath -> Validator -> IO ()
writeCBOR path val = do
  let bytes = LBS.toStrict (Serialise.serialise val)
      hex   = B16.encode bytes
  BS.writeFile path hex
  putStrLn $ "CBOR hex written to: " <> path

------------------------------------------------------------------------
-- Main
------------------------------------------------------------------------

main :: IO ()
main = do
  let network = C.Testnet (C.NetworkMagic 1)

  writeValidator "community_pool.plutus" validator
  writeCBOR      "community_pool.cbor"   validator

  let vh      = plutusValidatorHash validator
      addr    = plutusScriptAddress
      bech32  = toBech32ScriptAddress network validator
      cborHex = validatorToCBORHex validator

  putStrLn "\n--- Community Savings Pool ---"
  putStrLn $ "Validator Hash: " <> P.show vh
  putStrLn $ "Script Address: " <> P.show addr
  putStrLn $ "Bech32 Address: " <> bech32
  putStrLn $ "CBOR Hex (first 120 chars): " <> P.take 120 cborHex <> "..."
  putStrLn "-------------------------------"
