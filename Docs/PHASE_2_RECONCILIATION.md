# Phase 2.1 — Sync Engine Reconciliation

## Namespace migration

- [ ] BlackPrint\Sync → BlackPrint\Commerce\Sync

## Registries

- [ ] ConnectorRegistry
- [ ] StageRegistry

## Pipeline

- [ ] SyncPipeline

## Value Objects

- [ ] SupplierMetadata
- [ ] SupplierResponse
- [ ] HttpResponse

## Contracts

- [ ] SupplierConnector
- [ ] SupportsProducts
- [ ] SupportsStock
- [ ] SupportsPricing
- [ ] SupportsBranding

## Stages

- [ ] ProductsStage
- [ ] StockStage
- [ ] PricingStage
- [ ] BrandingStage

## Repositories

- [ ] Choose canonical repository layer

## Bootstrap

- [ ] Register SyncServiceProvider inside Loader

## Validation

- [ ] Plugin boots
- [ ] Dashboard loads
- [ ] Amrod connector works
- [ ] Products endpoint works
- [ ] No fatal errors