# BlackPrint OS Canonical Architecture

## Root Namespace

BlackPrint\Commerce

## Sync Namespace

BlackPrint\Commerce\Sync

## Source of Truth

Supplier API
    ↓
Raw Snapshot
    ↓
Canonical Commerce Model
    ↓
Business Enrichment
    ↓
Projection Layer
    ↓
WooCommerce

## Sync Engine

Scheduler
    ↓
SyncManager
    ↓
JobRunner
    ↓
JobDispatcher
    ↓
SyncPipeline
    ↓
Connector
    ↓
Snapshot Storage

## Rules

- WooCommerce is not the source of truth.
- Suppliers are not the source of truth.
- BlackPrint OS is the source of truth.
- Supplier logic belongs only in connectors.
- Sync Engine must remain supplier-agnostic.