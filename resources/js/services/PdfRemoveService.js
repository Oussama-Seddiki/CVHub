/**
 * PdfRemoveService.js
 * Service specifically for removing pages from PDF files
 */

import { PDFDocument } from 'pdf-lib';

class PdfRemoveService {
    /**
     * Remove specific pages from a PDF file
     * 
     * @param {File} file - The PDF file to remove pages from
     * @param {String} pagesToRemove - Comma-separated string of page numbers to remove (e.g., "1,3,5-7")
     * @returns {Promise<Object>} Promise with the result
     */
    async removePages(file, pagesToRemove) {
        try {
            // Validate input file
            if (!file) {
                return {
                    success: false,
                    message: 'No file provided for page removal'
                };
            }
            
            // Validate file type
            if (file.type !== 'application/pdf') {
                console.warn(`File ${file.name} is not a PDF, type: ${file.type}`);
                return {
                    success: false,
                    message: `File "${file.name}" is not a valid PDF file. Only PDF files can be processed.`
                };
            }
            
            // Get page numbers to remove
            const pageNumbers = this._parsePageRanges(pagesToRemove);
            
            if (pageNumbers.length === 0) {
                return {
                    success: false,
                    message: 'No valid page numbers specified'
                };
            }
            
            // Convert file to array buffer
            const fileBuffer = await file.arrayBuffer();
            
            // Check if buffer is valid
            if (!fileBuffer || fileBuffer.byteLength === 0) {
                console.warn(`File ${file.name} has zero bytes`);
                return {
                    success: false,
                    message: `File "${file.name}" appears to be empty.`
                };
            }
            
            // Check for PDF signature (%PDF-)
            const header = new Uint8Array(fileBuffer.slice(0, 5));
            const headerStr = String.fromCharCode.apply(null, header);
            if (headerStr !== '%PDF-') {
                console.warn(`File ${file.name} doesn't have a valid PDF header: ${headerStr}`);
                return {
                    success: false,
                    message: `File "${file.name}" is not a valid PDF file or may be corrupted.`
                };
            }
            
            // Load the PDF document with robust options
            const pdfDoc = await PDFDocument.load(fileBuffer, {
                ignoreEncryption: true,
                throwOnInvalidObject: false
            });
            
            // Get total pages
            const pageCount = pdfDoc.getPageCount();
            console.log(`File ${file.name} has ${pageCount} pages`);
            
            if (pageCount === 0) {
                return {
                    success: false,
                    message: 'The PDF document has no pages'
                };
            }
            
            // Validate page numbers
            const validPageNumbers = pageNumbers.filter(num => num > 0 && num <= pageCount);
            
            if (validPageNumbers.length === 0) {
                return {
                    success: false,
                    message: `Invalid page numbers. The document has ${pageCount} pages.`
                };
            }
            
            if (validPageNumbers.length === pageCount) {
                return {
                    success: false,
                    message: 'Cannot remove all pages from the document.'
                };
            }
            
            // Create a new PDF document
            const newPdf = await PDFDocument.create();
            
            // Copy all pages except those to be removed
            for (let i = 0; i < pageCount; i++) {
                // Skip pages that should be removed
                if (!validPageNumbers.includes(i + 1)) {
                    const [copiedPage] = await newPdf.copyPages(pdfDoc, [i]);
                    newPdf.addPage(copiedPage);
                }
            }
            
            // Save the PDF
            const pdfBytes = await newPdf.save();
            
            // Create a blob and URL for download
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            
            return {
                success: true,
                message: 'Pages removed successfully',
                file: url,
                filename: `modified-document-${Date.now()}.pdf`,
                removedPages: validPageNumbers
            };
        } catch (error) {
            console.error('Error removing PDF pages:', error);
            return {
                success: false,
                message: `Failed to remove PDF pages: ${error.message}`,
                error: error
            };
        }
    }
    
    /**
     * Parse a string of page ranges into an array of page numbers
     * 
     * @private
     * @param {String} pageRanges - String like "1,3-5,7"
     * @returns {Array<Number>} Array of page numbers
     */
    _parsePageRanges(pageRanges) {
        if (!pageRanges || typeof pageRanges !== 'string') {
            return [];
        }
        
        const result = [];
        const ranges = pageRanges.split(',');
        
        for (const range of ranges) {
            const trimmedRange = range.trim();
            
            if (!trimmedRange) continue;
            
            if (trimmedRange.includes('-')) {
                // It's a range
                const [start, end] = trimmedRange.split('-').map(num => parseInt(num.trim(), 10));
                
                if (!isNaN(start) && !isNaN(end)) {
                    for (let i = start; i <= end; i++) {
                        if (!result.includes(i)) {
                            result.push(i);
                        }
                    }
                }
            } else {
                // It's a single page
                const pageNum = parseInt(trimmedRange, 10);
                
                if (!isNaN(pageNum) && !result.includes(pageNum)) {
                    result.push(pageNum);
                }
            }
        }
        
        return result.sort((a, b) => a - b);
    }
}

export default new PdfRemoveService(); 