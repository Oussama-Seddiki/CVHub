/**
 * Services/PdfProcessingService.js
 * Facade for all PDF services
 */

import PdfService from './PdfService';
import ImagesToPdfService from './ImagesToPdfService';
import WordToPdfService from './WordToPdfService';
import PptToPdfService from './PptToPdfService';
import ExcelToPdfService from './ExcelToPdfService';
import OcrService from './OcrService';
import PdfToImageService from './PdfToImageService';

/**
 * Service facade that delegates to individual specialized services
 */
class PdfProcessingService {
    /**
     * Check service status
     * 
     * @returns {Promise<Object>} Service status response
     */
    async checkApiStatus() {
        try {
            const response = await PdfService.checkServiceStatus();
            return {
                success: true,
                message: 'PDF services are operational',
                details: response.details || {}
            };
        } catch (error) {
            console.error('Error checking PDF service status:', error);
            return {
                success: false,
                message: 'PDF services are not available',
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
            const operationMap = {
                'compress': 'merge', // Using merge as temporary replacement
                'extract_pages': 'extract',
                'organize_pages': 'organize',
                'remove_pages': 'remove',
                'preview': 'preview'
            };
            
            const newOperation = operationMap[operation] || operation;
            console.log(`Processing PDF with operation: ${newOperation}`);
            
            return await PdfService.processPdf(file, newOperation, options);
        } catch (error) {
            console.error(`Error processing PDF (${operation}):`, error);
            return {
                success: false,
                message: `Failed to process PDF: ${error.message}`,
                error: error.message
            };
        }
    }

    /**
     * Process images
     * 
     * @param {File|Array} files - The image file(s) to process
     * @param {string} operation - The operation to perform
     * @param {Object} options - Operation options
     * @returns {Promise<Object>} Operation result
     */
    async processImages(files, operation, options = {}) {
        try {
            console.log(`Processing images for operation: ${operation}`);
            
            // Handle JPG to PDF conversion
            if (operation === 'jpg-to-pdf') {
                return await ImagesToPdfService.convertImagesToPdf(files, options);
            }
            
            // Handle PDF to image conversion
            if (operation === 'pdf-to-jpg' || operation === 'pdf-to-png') {
                const format = operation === 'pdf-to-jpg' ? 'jpg' : 'png';
                return await PdfToImageService.convertPdfToImage(files, format, options);
            }
            
            return {
                success: false,
                message: `Unsupported image operation: ${operation}`
            };
        } catch (error) {
            console.error(`Error processing images (${operation}):`, error);
            return {
                success: false,
                message: `Failed to process images: ${error.message}`,
                error: error.message
            };
        }
    }

    /**
     * Process documents
     * 
     * @param {File} file - The document file to process
     * @param {string} operation - The operation to perform
     * @param {Object} options - Operation options
     * @returns {Promise<Object>} Operation result
     */
    async processDocuments(file, operation, options = {}) {
        try {
            console.log(`Processing document operation: ${operation}`);
            
            // Handle Word to PDF conversion
            if (operation === 'word-to-pdf') {
                return await WordToPdfService.convertWordToPdf(file, options);
            }
            
            // Handle PowerPoint to PDF conversion
            if (operation === 'ppt-to-pdf') {
                return await PptToPdfService.convertPptToPdf(file, options);
            }
            
            // Handle Excel to PDF conversion
            if (operation === 'excel-to-pdf') {
                return await ExcelToPdfService.convertExcelToPdf(file, options);
            }
            
            // For future operations
            return {
                success: false,
                message: `Unsupported document operation: ${operation}`
            };
        } catch (error) {
            console.error(`Error processing document (${operation}):`, error);
            return {
                success: false,
                message: `Failed to process document: ${error.message}`,
                error: error.message
            };
        }
    }

    /**
     * Process OCR on a PDF file
     * 
     * @param {File} file - The PDF file to process
     * @param {Object} options - OCR options
     * @returns {Promise<Object>} Operation result
     */
    async processOcr(file, options = {}) {
        return await OcrService.processOcr(file, options);
    }

    /**
     * Process PowerPoint to PDF conversion
     * 
     * @param {FormData|Object} formData - Form data containing the file and options
     * @returns {Promise<Object>} Operation result
     */
    async processPptToPdf(formData) {
        return await PptToPdfService.processPptToPdf(formData);
    }

    /**
     * Process Word to PDF conversion
     * 
     * @param {FormData|Object} formData - Form data containing the file and options
     * @returns {Promise<Object>} Operation result
     */
    async processWordToPdf(formData) {
        if (formData instanceof FormData) {
            const file = formData.get('file');
            const quality = formData.get('quality') || 'standard';
            const preserveFormatting = formData.get('preserve_formatting') === '1';
            
            return await WordToPdfService.convertWordToPdf(file, { 
                quality, 
                preserveFormatting 
            });
        } else if (formData && typeof formData === 'object') {
            return await WordToPdfService.convertWordToPdf(formData.file, {
                quality: formData.quality,
                preserveFormatting: formData.preserveFormatting
            });
        } else {
            throw new Error('Invalid input: Expected FormData or object with file property');
        }
    }
}

export default new PdfProcessingService();
