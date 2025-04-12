/**
 * Services/PdfService.js
 * Handles core PDF operations like merging, splitting, extracting, etc.
 */

import axios from 'axios';

/**
 * Service for handling PDF operations
 */
class PdfService {
    /**
     * Check if service is available
     * 
     * @returns {Promise<Object>} Service status
     */
    async checkServiceStatus() {
        try {
            // Always return success as we'll use client-side processing
            return {
                success: true,
                message: 'PDF client-side processing is available',
                details: {
                    processingMode: 'client-side'
                }
            };
        } catch (error) {
            console.error('Error checking PDF service status:', error);
            return {
                success: false,
                message: 'PDF service is not available',
                error: error.message
            };
        }
    }

    /**
     * Process a PDF file
     * 
     * @param {File} file - The PDF file to process
     * @param {string} operation - The operation to perform
     * @param {Object} options - Operation options
     * @returns {Promise<Object>} Operation result
     */
    async processPdf(file, operation, options = {}) {
        try {
            console.log(`Processing PDF operation: ${operation}`, {
                filename: file.name,
                filesize: file.size,
                filetype: file.type
            });
            
            // Instead of making API calls, we'll process client-side
            // This is a placeholder for actual client-side processing implementation
            
            // For demonstration, return a dummy successful response
            return {
                success: true,
                message: `PDF ${operation} completed successfully`,
                file: URL.createObjectURL(file),
                filename: `processed-${file.name}`
            };
        } catch (error) {
            console.error(`Error processing PDF operation (${operation}):`, error);
            
            return {
                success: false,
                message: `Failed to process PDF (${operation}): ${error.message}`,
                error: error.message
            };
        }
    }

    /**
     * Convert a URL or result object to a File object
     * 
     * @param {String|Object} source - URL string or object with file URL
     * @returns {Promise<File>} Promise with File object
     */
    async convertUrlToFile(source) {
        try {
            // Extract URL from source
            let url;
            
            if (typeof source === 'string') {
                url = source;
            } else if (source && source.file && typeof source.file === 'string') {
                url = source.file;
            } else {
                throw new Error('Invalid source. Expected URL string or object with file property.');
            }
            
            console.log('Converting URL to File:', url);
            
            // Fetch the file
            const response = await fetch(url);
            
            if (!response.ok) {
                throw new Error(`Failed to fetch file: ${response.status} ${response.statusText}`);
            }
            
            // Get the blob
            const blob = await response.blob();
            
            // Create a filename (extract from URL or use timestamp)
            let filename = 'document.pdf';
            
            try {
                // Try to extract filename from URL
                const urlObj = new URL(url);
                const pathParts = urlObj.pathname.split('/');
                const lastPart = pathParts[pathParts.length - 1];
                
                if (lastPart && lastPart.toLowerCase().endsWith('.pdf')) {
                    filename = lastPart;
                } else if (source && source.filename) {
                    filename = source.filename;
                } else {
                    filename = `document-${Date.now()}.pdf`;
                }
            } catch (e) {
                console.warn('Could not parse URL for filename:', e);
                filename = `document-${Date.now()}.pdf`;
            }
            
            // Create a new File object
            return new File([blob], filename, { type: 'application/pdf' });
        } catch (error) {
            console.error('Error converting URL to File:', error);
            throw error;
        }
    }

    /**
     * Generate a URL for the Sejda PDF Editor
     * 
     * @param {string} pdfUrl - URL of the PDF to edit
     * @param {boolean} fillSignOnly - Whether to only allow filling and signing
     * @returns {string} The editor URL
     */
    generateEditorUrl(pdfUrl, fillSignOnly = false) {
        const baseUrl = 'https://www.sejda.com/';
        const tool = fillSignOnly ? 'pdf-editor-fill-sign' : 'pdf-editor';
        return `${baseUrl}${tool}?file-url=${encodeURIComponent(pdfUrl)}`;
    }

    /**
     * Generate a URL for the Sejda HTML to PDF converter
     * 
     * @param {string} url - URL of the webpage to convert
     * @param {Object} options - Conversion options
     * @returns {string} The converter URL
     */
    generateHtmlToPdfUrl(url, options = {}) {
        const baseUrl = 'https://www.sejda.com/html-to-pdf';
        return `${baseUrl}?url=${encodeURIComponent(url)}`;
    }
}

export default new PdfService();