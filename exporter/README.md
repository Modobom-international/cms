# Page Exporter

This exporter is automatically triggered by the CMS when a page export is requested.

## How it works

1. The CMS triggers this exporter by running `pnpm build` in this directory
2. The exporter fetches the latest export request from the API
3. The exporter fetches the pages to export from the API
4. The exported files are generated in the `dist` directory

## Setup

1. Copy `.env.example` to `.env` and configure as needed:
   ```
   cp .env.example .env
   ```
2. Install dependencies:
   ```
   pnpm install
   ```

## Manual Testing

You can manually test the exporter by running:

```
pnpm build
```

The exporter will fetch the latest export request from the API and generate the exported files in the `dist` directory.

## Development

- The main entry point is `src/index.js`
- Exported files are stored in the `dist` directory
- The exporter keeps the 10 most recent export files and deletes older ones
