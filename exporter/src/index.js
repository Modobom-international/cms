/**
 * Page Exporter
 * 
 * This script is triggered by the CMS when an export request is created.
 * It fetches the latest export request from the API,
 * and generates the exported files.
 */

// Import required dependencies
const axios = require('axios');
const fs = require('fs');
const path = require('path');
require('dotenv').config();

// Configuration
const API_BASE_URL = process.env.API_BASE_URL || 'http://localhost:8000/api';
const OUTPUT_DIR = path.join(__dirname, '../dist');

console.log('Starting page exporter');

// Create the output directory if it doesn't exist
if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
}

// API client
const api = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

/**
 * Fetch the latest export request
 */
async function fetchLatestExport() {
    try {
        const response = await api.get('/pending-exports');
        console.log(response.data.data);
        return response.data.data;
    } catch (error) {
        console.error('Error fetching latest export:', error.message);
        throw error;
    }
}

/**
 * Fetch a page by slug
 */
async function fetchPage(slug) {
    try {
        const response = await api.get(`/page/${slug}`);
        return response.data.data;
    } catch (error) {
        console.error(`Error fetching page ${slug}:`, error.message);
        throw error;
    }
}

/**
 * Cleanup previous export files
 */
function cleanupPreviousExports() {
    try {
        if (fs.existsSync(OUTPUT_DIR)) {
            const files = fs.readdirSync(OUTPUT_DIR);
            if (files.length > 10) { // Limit to keeping 10 most recent exports
                // Sort files by creation time (newest first)
                const sortedFiles = files
                    .map(file => ({
                        name: file,
                        time: fs.statSync(path.join(OUTPUT_DIR, file)).mtime.getTime()
                    }))
                    .sort((a, b) => b.time - a.time);

                // Delete older files (keep 10 newest)
                sortedFiles.slice(10).forEach(file => {
                    fs.unlinkSync(path.join(OUTPUT_DIR, file.name));
                    console.log(`Deleted old export file: ${file.name}`);
                });
            }
        }
    } catch (error) {
        console.error('Error cleaning up previous exports:', error.message);
    }
}

/**
 * Process the export
 */
async function processExport() {
    console.log('Processing latest export request');

    try {
        // Clean up previous export files
        cleanupPreviousExports();

        // Fetch latest export
        const exportData = await fetchLatestExport();

        if (!exportData) {
            console.log('No export requests found. Exiting.');
            return;
        }

        const exportId = exportData.id;
        const slugs = exportData.slugs;

        console.log(`Processing export ID: ${exportId}`);
        console.log(`Found ${slugs.length} pages to export: ${slugs.join(', ')}`);

        // Fetch all pages
        const pages = [];
        for (const slug of slugs) {
            console.log(`Fetching page: ${slug}`);
            const page = await fetchPage(slug);
            pages.push(page);
        }

        // Generate output files
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const outputFile = path.join(OUTPUT_DIR, `pages-${exportId}-${timestamp}.json`);

        // Write the pages to a JSON file
        fs.writeFileSync(outputFile, JSON.stringify(pages, null, 2));
        console.log(`Pages exported to: ${outputFile}`);
        console.log('Export completed successfully');
    } catch (error) {
        console.error('Export process failed:', error.message);
        process.exit(1);
    }
}

// Run the export process
processExport().catch(err => {
    console.error('Unhandled error in export process:', err);
    process.exit(1);
}); 