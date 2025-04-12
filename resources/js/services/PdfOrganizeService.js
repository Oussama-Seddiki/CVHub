/**
 * PdfOrganizeService.js
 * Service for organizing (reordering, rotating) pages of PDF files
 */

import { PDFDocument, degrees } from 'pdf-lib';

class PdfOrganizeService {
    /**
     * Rearrange and rotate pages in a PDF file
     * 
     * @param {File} file - The PDF file to modify
     * @param {Array<Object>} pageOperations - Array of objects with page operations:
     *                                         [{pageNumber: Number, rotation: Number, newPosition: Number}]
     * @param {Object} metadata - Optional metadata for the output PDF
     * @returns {Promise<Object>} Promise with the result (download URL)
     */
    async organizePdf(file, pageOperations, metadata = {}) {
        try {
            // Validate input file
            if (!file) {
                return {
                    success: false,
                    message: 'No file provided for organizing'
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
            
            // Validate page operations
            if (!Array.isArray(pageOperations) || pageOperations.length === 0) {
                return {
                    success: false,
                    message: 'No page operations specified'
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
            
            // Create a new PDF document
            const newPdf = await PDFDocument.create();
            
            // Set metadata for the new PDF document if provided
            if (metadata.title) newPdf.setTitle(metadata.title);
            if (metadata.author) newPdf.setAuthor(metadata.author);
            if (metadata.subject) newPdf.setSubject(metadata.subject);
            if (metadata.keywords) newPdf.setKeywords(metadata.keywords);
            if (metadata.creator) newPdf.setCreator(metadata.creator);
            if (metadata.producer) newPdf.setProducer(metadata.producer);
            
            // Copy all pages in the new order and rotate them as needed
            const sortedOperations = [...pageOperations].sort((a, b) => 
                (a.newPosition || 0) - (b.newPosition || 0)
            );
            
            // Validate page operations
            const validOperations = sortedOperations.filter(op => 
                op.pageNumber > 0 && 
                op.pageNumber <= pageCount && 
                (op.newPosition === undefined || (op.newPosition > 0 && op.newPosition <= pageCount))
            );
            
            if (validOperations.length === 0) {
                return {
                    success: false,
                    message: 'No valid page operations found'
                };
            }
            
            for (const op of validOperations) {
                const pageIndex = op.pageNumber - 1;
                
                try {
                    // Copy the page to the new document
                    const [copiedPage] = await newPdf.copyPages(pdfDoc, [pageIndex]);
                    
                    // Apply rotation if specified
                    if (op.rotation !== undefined && op.rotation !== 0) {
                        copiedPage.setRotation(degrees(op.rotation));
                    }
                    
                    // Add the page
                    newPdf.addPage(copiedPage);
                } catch (pageError) {
                    console.error(`Error processing page ${op.pageNumber}:`, pageError);
                    // Continue with other pages even if one fails
                }
            }
            
            // Save the PDF
            const pdfBytes = await newPdf.save();
            
            // Create a blob and URL for download
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            
            return {
                success: true,
                message: `PDF pages successfully organized`,
                file: url,
                filename: `organized-document-${Date.now()}.pdf`
            };
        } catch (error) {
            console.error('Error organizing PDF pages:', error);
            return {
                success: false,
                message: `Failed to organize PDF pages: ${error.message}`,
                error: error
            };
        }
    }
}

export default new PdfOrganizeService(); 