/**
 * PdfSplitService.js
 * Service specifically for PDF splitting operations
 */

import { PDFDocument } from 'pdf-lib';

class PdfSplitService {
    /**
     * Split a PDF file into separate PDF files
     * 
     * @param {File} file - The PDF file to split
     * @returns {Promise<Object>} Promise with the split result
     */
    async splitPdf(file) {
        try {
            // Convert file to array buffer
            const fileBuffer = await file.arrayBuffer();
            
            // Load the PDF document
            const pdfDoc = await PDFDocument.load(fileBuffer);
            
            // Get total pages
            const pageCount = pdfDoc.getPageCount();
            
            // Create an array to hold all the split PDFs
            const splitPdfs = [];
            
            // Process each page
            for (let i = 0; i < pageCount; i++) {
                // Create a new PDF document
                const newPdf = await PDFDocument.create();
                
                // Copy the current page
                const [copiedPage] = await newPdf.copyPages(pdfDoc, [i]);
                
                // Add the page to the new document
                newPdf.addPage(copiedPage);
                
                // Save the PDF
                const pdfBytes = await newPdf.save();
                
                // Create a blob and URL for download
                const blob = new Blob([pdfBytes], { type: 'application/pdf' });
                const url = URL.createObjectURL(blob);
                
                // Add to the array of split PDFs
                splitPdfs.push({
                    url,
                    pageNumber: i + 1,
                    filename: `page-${i + 1}.pdf`
                });
                
                // Add a small delay to avoid UI freezing
                await new Promise(resolve => setTimeout(resolve, 10));
            }
            
            return {
                success: true,
                message: 'PDF file split successfully',
                files: splitPdfs,
                pageCount
            };
        } catch (error) {
            console.error('Error splitting PDF file:', error);
            return {
                success: false,
                message: `Failed to split PDF file: ${error.message}`,
                error: error
            };
        }
    }
}

export default new PdfSplitService(); 