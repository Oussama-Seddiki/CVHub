/**
 * PdfMergeService.js
 * Service specifically for PDF merging operations
 */

import { PDFDocument } from 'pdf-lib';

class PdfMergeService {
    /**
     * Merge multiple PDF files client-side using pdf-lib
     * 
     * @param {FileList|Array} files - The PDF files to merge
     * @returns {Promise<Object>} Promise with the merge result
     */
    async mergePdfs(files) {
        try {
            // Validate input
            if (!files || files.length === 0) {
                return {
                    success: false,
                    message: 'No files provided for merging'
                };
            }
            
            if (files.length < 2) {
                return {
                    success: false,
                    message: 'At least two PDF files are required for merging'
                };
            }
            
            // Create a new PDF document
            const mergedPdf = await PDFDocument.create();
            
            // Process each file
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                try {
                    // Handle different file types: File, URL string, or Blob URLs
                    let fileBuffer;
                    
                    // Check if this is a file object or a string/URL
                    if (typeof file === 'string') {
                        // Handle string URLs (could be from previous PDF operations)
                        console.log(`Processing URL at index ${i}`);
                        
                        try {
                            const response = await fetch(file);
                            if (!response.ok) {
                                throw new Error(`Failed to fetch URL: ${response.status} ${response.statusText}`);
                            }
                            fileBuffer = await response.arrayBuffer();
                        } catch (urlError) {
                            console.error(`Error fetching URL at index ${i}:`, urlError);
                            return {
                                success: false,
                                message: `Failed to load file at position ${i+1}: ${urlError.message}`
                            };
                        }
                    } else if (file instanceof Blob) {
                        // Handle Blob objects - Always try to read as text first to detect JSON
                        console.log(`Processing Blob at index ${i}`);
                        
                        try {
                            // Try to detect JSON no matter what the content type is
                            const text = await file.text();
                            const firstFewChars = text.trim().substring(0, 20);
                            
                            // Check if it looks like JSON
                            if (firstFewChars.startsWith('{') || firstFewChars.startsWith('[')) {
                                console.log(`Blob at index ${i} starts with JSON characters:`, firstFewChars);
                                
                                // Try parsing as JSON if it looks like it
                                try {
                                    const jsonObj = JSON.parse(text);
                                    console.log(`Successfully parsed JSON at index ${i}:`, jsonObj);
                                    
                                    // Check for file URL in JSON
                                    if (jsonObj.success === true && jsonObj.file && typeof jsonObj.file === 'string') {
                                        console.log(`Found file URL in JSON at index ${i}:`, jsonObj.file);
                                        
                                        const response = await fetch(jsonObj.file);
                                        if (!response.ok) {
                                            throw new Error(`Failed to fetch JSON file URL: ${response.status} ${response.statusText}`);
                                        }
                                        fileBuffer = await response.arrayBuffer();
                                        console.log(`Successfully fetched file from JSON URL at index ${i}`);
                                    } else {
                                        console.warn(`JSON at index ${i} doesn't contain a valid file URL`);
                                        return {
                                            success: false,
                                            message: `File at position ${i+1} contains JSON data but no valid PDF URL.`
                                        };
                                    }
                                } catch (jsonParseError) {
                                    console.warn(`Failed to parse JSON-like content at index ${i}:`, jsonParseError);
                                    // If it looks like JSON but isn't valid, it's probably not a PDF either
                                    return {
                                        success: false,
                                        message: `File at position ${i+1} appears to be malformed JSON, not a valid PDF.`
                                    };
                                }
                            } else {
                                // Not JSON, proceed with normal file processing
                                console.log(`Blob at index ${i} is not JSON, processing as PDF`);
                                fileBuffer = await file.arrayBuffer();
                            }
                        } catch (textReadError) {
                            console.warn(`Failed to read Blob at index ${i} as text:`, textReadError);
                            // Fall back to binary processing
                            fileBuffer = await file.arrayBuffer();
                        }
                    } else if (typeof file === 'object' && file.file && typeof file.file === 'string') {
                        // Handle JSON response objects with file URLs from other services
                        console.log(`Processing file URL object at index ${i}`);
                        try {
                            const response = await fetch(file.file);
                            if (!response.ok) {
                                throw new Error(`Failed to fetch file URL: ${response.status} ${response.statusText}`);
                            }
                            fileBuffer = await response.arrayBuffer();
                        } catch (urlError) {
                            console.error(`Error fetching file URL object at index ${i}:`, urlError);
                            return {
                                success: false,
                                message: `Failed to load file at position ${i+1}: ${urlError.message}`
                            };
                        }
                    } else {
                        console.warn(`Unsupported file type at index ${i}:`, file);
                        return {
                            success: false,
                            message: `Unsupported file type at position ${i+1}. Please provide valid PDF files.`
                        };
                    }
                    
                    // Make sure we have a buffer at this point
                    if (!fileBuffer) {
                        console.error(`Failed to obtain file buffer for item at index ${i}`);
                        return {
                            success: false,
                            message: `Could not process file at position ${i+1}.`
                        };
                    }
                    
                    // Check if buffer is valid
                    if (fileBuffer.byteLength === 0) {
                        const fileName = file.name || `File at position ${i+1}`;
                        console.warn(`${fileName} has zero bytes`);
                        return {
                            success: false,
                            message: `${fileName} appears to be empty.`
                        };
                    }
                    
                    // Check for PDF signature (%PDF-)
                    const header = new Uint8Array(fileBuffer.slice(0, 5));
                    const headerStr = String.fromCharCode.apply(null, header);
                    if (headerStr !== '%PDF-') {
                        const fileName = file.name || `File at position ${i+1}`;
                        console.warn(`${fileName} doesn't have a valid PDF header: ${headerStr}`);
                        
                        // Special handling for JSON disguised as PDF
                        if (headerStr.startsWith('{') || headerStr.startsWith('[')) {
                            console.warn(`File at position ${i+1} appears to be JSON, not PDF`);
                            // Try to read more of the buffer as text to extract JSON
                            try {
                                const jsonText = new TextDecoder().decode(fileBuffer);
                                console.log(`JSON content found:`, jsonText.substring(0, 100));
                                
                                if (jsonText.includes('"file"') && jsonText.includes('"success"')) {
                                    try {
                                        const jsonObj = JSON.parse(jsonText);
                                        if (jsonObj.success && jsonObj.file) {
                                            console.log(`Found file URL in JSON buffer:`, jsonObj.file);
                                            // Fetch the real PDF from the URL
                                            const response = await fetch(jsonObj.file);
                                            if (!response.ok) {
                                                throw new Error(`Failed to fetch PDF from JSON URL: ${response.status}`);
                                            }
                                            // Replace the fileBuffer with the real PDF content
                                            fileBuffer = await response.arrayBuffer();
                                            console.log(`Successfully fetched PDF from JSON URL`);
                                            
                                            // Verify the new buffer is a PDF
                                            const newHeader = new Uint8Array(fileBuffer.slice(0, 5));
                                            const newHeaderStr = String.fromCharCode.apply(null, newHeader);
                                            if (newHeaderStr !== '%PDF-') {
                                                throw new Error('Retrieved content is not a valid PDF');
                                            }
                                        } else {
                                            throw new Error('JSON does not contain a valid file URL');
                                        }
                                    } catch (jsonError) {
                                        console.error(`Failed to process JSON in buffer:`, jsonError);
                                        return {
                                            success: false,
                                            message: `File at position ${i+1} contains invalid JSON data: ${jsonError.message}`
                                        };
                                    }
                                } else {
                                    return {
                                        success: false,
                                        message: `File at position ${i+1} is not a valid PDF file or may be corrupted.`
                                    };
                                }
                            } catch (textError) {
                                console.error(`Failed to decode buffer as text:`, textError);
                                return {
                                    success: false,
                                    message: `File at position ${i+1} is not a valid PDF file or may be corrupted.`
                                };
                            }
                        } else {
                            return {
                                success: false,
                                message: `${fileName} is not a valid PDF file or may be corrupted.`
                            };
                        }
                    }
                    
                    const fileName = file.name || `File at position ${i+1}`;
                    const fileSize = file.size || fileBuffer.byteLength;
                    console.log(`Processing file ${i+1}/${files.length}: ${fileName}, size: ${fileSize} bytes`);
                    
                    // Load the PDF document
                    const pdf = await PDFDocument.load(fileBuffer, {
                        ignoreEncryption: true,
                        throwOnInvalidObject: false
                    });
                    
                    // Check if document is valid
                    const pageCount = pdf.getPageCount();
                    console.log(`File ${fileName} has ${pageCount} pages`);
                    
                    if (pageCount === 0) {
                        console.warn(`File ${fileName} has 0 pages`);
                        return {
                            success: false,
                            message: `${fileName} has no pages to merge.`
                        };
                    }
                    
                    // Get all pages from the document
                    const pages = pdf.getPages();
                    const copiedPages = await mergedPdf.copyPages(pdf, pages.map((_, i) => i));
                    
                    // Add each page to the new document
                    copiedPages.forEach(page => mergedPdf.addPage(page));
                    
                    // Add a small delay to avoid UI freezing
                    await new Promise(resolve => setTimeout(resolve, 10));
                } catch (fileError) {
                    const fileName = file.name || `File at position ${i+1}`;
                    console.error(`Error processing file ${fileName}:`, fileError);
                    return {
                        success: false,
                        message: `Error processing file "${fileName}": ${fileError.message}`
                    };
                }
            }
            
            // Save the merged PDF
            const mergedPdfBytes = await mergedPdf.save();
            
            // Create a blob and URL for download
            const blob = new Blob([mergedPdfBytes], { type: 'application/pdf' });
            const url = URL.createObjectURL(blob);
            
            return {
                success: true,
                message: 'PDF files merged successfully',
                file: url,
                filename: `merged-document-${Date.now()}.pdf`
            };
        } catch (error) {
            console.error('Error merging PDF files:', error);
            return {
                success: false,
                message: `Failed to merge PDF files: ${error.message}`,
                error: error
            };
        }
    }
}

export default new PdfMergeService(); 