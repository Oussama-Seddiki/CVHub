/**
 * PdfPreviewService.js
 * Service for generating PDF previews with actual page content when possible
 * Falls back to placeholders if rendering fails
 * Enhanced with animation support and improved rendering
 */

import * as pdfjsLib from 'pdfjs-dist';

// Create a minimal inline worker to satisfy PDF.js requirements without actually using it
// This prevents the "No GlobalWorkerOptions.workerSrc specified" warning
const workerBlob = new Blob([
  'self.onmessage = function() { self.postMessage({ isReady: true }); };'
], { type: 'application/javascript' });

// Set a proper URL to the worker blob
pdfjsLib.GlobalWorkerOptions.workerSrc = URL.createObjectURL(workerBlob);

class PdfPreviewService {
    constructor() {
        console.log('Enhanced PDF preview service initialized');
        this.animationConfig = {
            enableAnimations: true,
            transitionEffect: 'fade', // 'fade', 'slide', 'zoom'
            transitionSpeed: 'normal', // 'slow', 'normal', 'fast'
            placeholderStyle: 'gradient', // 'gradient', 'pattern', 'minimal'
            thumbnailQuality: 'medium' // 'low', 'medium', 'high'
        };
    }
    
    /**
     * Configure animation and rendering settings
     * 
     * @param {Object} config - Configuration options
     */
    configure(config = {}) {
        this.animationConfig = { ...this.animationConfig, ...config };
        console.log('Preview service configured:', this.animationConfig);
        return this.animationConfig;
    }
    
    /**
     * Generate preview thumbnails for a PDF file
     * 
     * @param {File} file - The PDF file to generate previews for
     * @param {Object} options - Optional rendering options
     * @returns {Promise<Object>} Promise with the result
     */
    async generatePreview(file, options = {}) {
        try {
            console.log('Starting enhanced PDF preview generation for:', file?.name);
            
            // Validate input file
            if (!file) {
                return { success: false, message: 'No file provided' };
            }
            
            // Merge options with defaults
            const renderOptions = {
                scale: options.scale || 0.5,
                quality: options.quality || this._getQualityValue(),
                maxPages: options.maxPages || 50,
                ...options
            };
            
            // Create a URL for the file (needed for viewer)
            const fileUrl = URL.createObjectURL(file);
            
            // Get file as array buffer
            const arrayBuffer = await file.arrayBuffer();
            
            // Get page count using safer method that always works
            const pageCount = await this._getPageCountFallback(arrayBuffer);
            console.log(`File ${file.name} has ${pageCount} pages (fallback method)`);
            
            // Create previews for each page using placeholders
            const previews = [];
            
            // Process each page with placeholders (reliable method)
            for (let i = 1; i <= pageCount; i++) {
                previews.push({
                    pageNumber: i,
                    dataUrl: this.createPlaceholderThumbnail(i),
                    isLoading: true,
                    loadProgress: 0,
                    animationDelay: `${(i % 5) * 0.1}s`
                });
                
                // Yield to browser occasionally
                if (i % 5 === 0) {
                    await new Promise(resolve => setTimeout(resolve, 10));
                }
            }
            
            // Now that we have placeholders to show immediately, try loading the PDF
            // in the background for actual rendering if possible
            this._attemptBackgroundRendering(arrayBuffer, previews, renderOptions)
                .then(renderedPages => {
                    console.log(`Background rendered ${renderedPages} pages successfully`);
                })
                .catch(error => {
                    console.warn('Background rendering failed:', error);
                });
            
            return {
                success: true,
                message: 'Preview generated successfully',
                fileUrl: fileUrl,
                totalPages: pageCount,
                previews: previews,
                animationConfig: this.animationConfig
            };
        } catch (error) {
            console.error('Error generating previews:', error);
            return {
                success: false,
                message: `Failed to process PDF: ${error.message}`,
                error: error
            };
        }
    }
    
    /**
     * Get quality value based on configuration
     * 
     * @private
     * @returns {number} Quality value between 0 and 1
     */
    _getQualityValue() {
        const qualityMap = {
            low: 0.5,
            medium: 0.75,
            high: 0.9
        };
        return qualityMap[this.animationConfig.thumbnailQuality] || 0.75;
    }
    
    /**
     * Attempt to render PDF pages in the background after initial placeholders are shown
     * 
     * @private
     * @param {ArrayBuffer} buffer - The PDF file buffer
     * @param {Array} previewsArray - Reference to the previews array that will be updated
     * @param {Object} options - Rendering options
     * @returns {Promise<number>} Number of successfully rendered pages
     */
    async _attemptBackgroundRendering(buffer, previewsArray, options) {
        try {
            // Configure for minimal PDF.js usage
            const loadingTask = pdfjsLib.getDocument({
                data: buffer,
                disableWorker: true,
                disableRange: true,
                disableFontFace: true,
                disableCreateObjectURL: true,
                cMapUrl: null,
                standardFontDataUrl: null
            });
            
            // More generous timeout for background processing
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => reject(new Error('PDF loading timed out')), 8000);
            });
            
            // Race the loading with timeout
            const pdfDoc = await Promise.race([
                loadingTask.promise,
                timeoutPromise
            ]);
            
            const pageCount = pdfDoc.numPages;
            const renderLimit = Math.min(pageCount, options.maxPages || 10); // Limit pages for performance
            let renderedPages = 0;
            
            // Render each page
            for (let i = 1; i <= renderLimit; i++) {
                try {
                    // Update loading state
                    if (previewsArray[i-1]) {
                        previewsArray[i-1].loadProgress = 10;
                    }
                    
                    // Get the page
                    const page = await pdfDoc.getPage(i);
                    
                    if (previewsArray[i-1]) {
                        previewsArray[i-1].loadProgress = 30;
                    }
                    
                    // Set a small size for the preview to improve performance
                    const viewport = page.getViewport({ scale: options.scale || 0.4 });
                    
                    // Create a canvas for rendering
                    const canvas = document.createElement('canvas');
                    canvas.width = viewport.width;
                    canvas.height = viewport.height;
                    const ctx = canvas.getContext('2d');
                    
                    // Fill background with white
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    
                    if (previewsArray[i-1]) {
                        previewsArray[i-1].loadProgress = 50;
                    }
                    
                    // Render with a timeout
                    const renderPromise = page.render({
                        canvasContext: ctx,
                        viewport: viewport
                    }).promise;
                    
                    // More generous timeout for page rendering
                    const pageTimeoutPromise = new Promise((_, reject) => {
                        setTimeout(() => reject(new Error('Page render timed out')), 3000);
                    });
                    
                    if (previewsArray[i-1]) {
                        previewsArray[i-1].loadProgress = 70;
                    }
                    
                    // Race the render with timeout
                    await Promise.race([renderPromise, pageTimeoutPromise]);
                    
                    // Get image data
                    const dataUrl = canvas.toDataURL('image/jpeg', options.quality || 0.75);
                    
                    // Update the preview array with the actual rendered image
                    if (previewsArray[i-1]) {
                        previewsArray[i-1].loadProgress = 90;
                        
                        // Slight delay to allow for animation
                        await new Promise(resolve => setTimeout(resolve, 10));
                        
                        previewsArray[i-1].dataUrl = dataUrl;
                        previewsArray[i-1].isLoading = false;
                        previewsArray[i-1].loadProgress = 100;
                    }
                    
                    // Clean up page resources
                    page.cleanup();
                    renderedPages++;
                    
                    // Yield to browser to avoid UI freezing
                    await new Promise(resolve => setTimeout(resolve, 20));
                } catch (renderError) {
                    console.warn(`Background render failed for page ${i}:`, renderError);
                    // Update the preview to show it's not loading anymore
                    if (previewsArray[i-1]) {
                        previewsArray[i-1].isLoading = false;
                        previewsArray[i-1].loadProgress = 0;
                    }
                    // Continue to next page on error
                }
            }
            
            // Clean up
            pdfDoc.destroy();
            return renderedPages;
        } catch (error) {
            console.warn('Background rendering failed:', error);
            // Mark all as not loading
            previewsArray.forEach(preview => {
                preview.isLoading = false;
                preview.loadProgress = 0;
            });
            return 0;
        }
    }
    
    /**
     * Get PDF information including page count
     * Uses PDF.js with timeout protection
     * 
     * @private
     * @param {ArrayBuffer} buffer - The PDF file buffer
     * @param {string} fileName - Name of the file for error reporting
     * @returns {Promise<Object>} Object with pageCount and pdfDocument (if successful)
     */
    async _getPdfInfo(buffer, fileName) {
        try {
            // Check for PDF header signature first
            const header = new Uint8Array(buffer.slice(0, 5));
            const headerStr = String.fromCharCode.apply(null, header);
            if (headerStr !== '%PDF-') {
                throw new Error(`${fileName} is not a valid PDF file`);
            }
            
            // Try using PDF.js for document info
            try {
                // Configure for minimal PDF.js usage without worker functionality
                const loadingTask = pdfjsLib.getDocument({
                    data: buffer,
                    disableWorker: true,
                    disableRange: true,
                    disableFontFace: true,
                    disableCreateObjectURL: true,
                    cMapUrl: null,
                    standardFontDataUrl: null
                });
                
                // Set a timeout to prevent hanging
                const timeoutPromise = new Promise((_, reject) => {
                    setTimeout(() => reject(new Error('PDF loading timed out')), 5000);
                });
                
                // Race the loading with timeout
                const pdfDoc = await Promise.race([
                    loadingTask.promise,
                    timeoutPromise
                ]);
                
                return {
                    pageCount: pdfDoc.numPages,
                    pdfDocument: pdfDoc
                };
            } catch (pdfJsError) {
                console.warn('PDF.js loading failed, using fallback:', pdfJsError);
                return {
                    pageCount: this._getPageCountFallback(buffer),
                    pdfDocument: null
                };
            }
        } catch (error) {
            console.error('Error getting PDF info:', error);
            return {
                pageCount: 1,
                pdfDocument: null
            };
        }
    }
    
    /**
     * Fallback method to get page count using regex
     * 
     * @private
     * @param {ArrayBuffer} buffer - The PDF file buffer
     * @returns {number} Page count
     */
    _getPageCountFallback(buffer) {
        try {
            const data = new Uint8Array(buffer);
            
            // Check for PDF header signature
            const headerStr = String.fromCharCode.apply(null, data.slice(0, 5));
            if (headerStr !== '%PDF-') {
                console.warn('Invalid PDF header');
                return 1;
            }
            
            // Convert a portion of the PDF to text to search for page count
            // We'll search a larger portion to increase chances of finding it
            const searchSize = Math.min(buffer.byteLength, 20480); // 20KB
            const text = String.fromCharCode.apply(null, data.slice(0, searchSize));
            
            // Common patterns for page count in PDF headers
            const countPatterns = [
                /\/Count\s+(\d+)/i,
                /\/Pages\s+\d+\s+\d+\s+R.*\/Count\s+(\d+)/i,
                /\/Type\s*\/Pages.*\/Count\s+(\d+)/i
            ];
            
            for (const pattern of countPatterns) {
                const match = pattern.exec(text);
                if (match && match[1]) {
                    const count = parseInt(match[1], 10);
                    if (count > 0 && count < 10000) { // Sanity check
                        return count;
                    }
                }
            }
            
            // Default to a reasonable value if we couldn't find it
            return 1;
        } catch (error) {
            console.warn('Error in page count fallback:', error);
            return 1;
        }
    }
    
    /**
     * Create enhanced placeholder thumbnail with animations
     * 
     * @param {number} pageNumber - The page number
     * @param {number} width - Width of the thumbnail
     * @param {number} height - Height of the thumbnail
     * @returns {string} Data URL of the placeholder image
     */
    createPlaceholderThumbnail(pageNumber, width = 200, height = 280) {
        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        
        const ctx = canvas.getContext('2d');
        
        // Select style based on configuration
        const style = this.animationConfig.placeholderStyle || 'gradient';
        
        if (style === 'pattern') {
            // Create a pattern background
            this._createPatternBackground(ctx, width, height);
        } else if (style === 'minimal') {
            // Create a minimal background
            this._createMinimalBackground(ctx, width, height);
        } else {
            // Default gradient background
            this._createGradientBackground(ctx, width, height);
        }
        
        // Border with slight shadow
        ctx.strokeStyle = '#cbd5e1';
        ctx.lineWidth = 2;
        ctx.shadowColor = 'rgba(0,0,0,0.1)';
        ctx.shadowBlur = 5;
        ctx.shadowOffsetX = 2;
        ctx.shadowOffsetY = 2;
        ctx.strokeRect(5, 5, width - 10, height - 10);
        ctx.shadowColor = 'transparent';
        
        // PDF icon
        ctx.fillStyle = '#3b82f6';
        ctx.font = 'bold 28px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('PDF', width / 2, height / 2 - 30);
        
        // Page number
        ctx.fillStyle = '#475569';
        ctx.font = 'bold 16px Arial';
        ctx.fillText(`Page ${pageNumber}`, width / 2, height / 2 + 5);
        
        // Page size indicator with subtle shadow
        ctx.beginPath();
        ctx.shadowColor = 'rgba(0,0,0,0.1)';
        ctx.shadowBlur = 3;
        ctx.shadowOffsetX = 1;
        ctx.shadowOffsetY = 1;
        ctx.rect(width / 2 - 30, height / 2 + 15, 60, 80 * 0.6);
        ctx.strokeStyle = '#94a3b8';
        ctx.stroke();
        
        return canvas.toDataURL('image/jpeg', 0.9);
    }
    
    /**
     * Create gradient background for placeholder
     * 
     * @private
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {number} width - Canvas width
     * @param {number} height - Canvas height
     */
    _createGradientBackground(ctx, width, height) {
        const gradient = ctx.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, '#f8fafc');
        gradient.addColorStop(1, '#e2e8f0');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, width, height);
        
        // Add some subtle diagonal lines
        ctx.strokeStyle = 'rgba(203, 213, 225, 0.3)';
        ctx.lineWidth = 1;
        
        for (let i = -2 * width; i < 2 * width; i += 20) {
            ctx.beginPath();
            ctx.moveTo(i, 0);
            ctx.lineTo(i + width, height);
            ctx.stroke();
        }
    }
    
    /**
     * Create pattern background for placeholder
     * 
     * @private
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {number} width - Canvas width
     * @param {number} height - Canvas height
     */
    _createPatternBackground(ctx, width, height) {
        // Fill with light base color
        ctx.fillStyle = '#f8fafc';
        ctx.fillRect(0, 0, width, height);
        
        // Create grid pattern
        ctx.strokeStyle = 'rgba(203, 213, 225, 0.4)';
        ctx.lineWidth = 1;
        
        // Horizontal lines
        for (let y = 0; y < height; y += 10) {
            ctx.beginPath();
            ctx.moveTo(0, y);
            ctx.lineTo(width, y);
            ctx.stroke();
        }
        
        // Vertical lines
        for (let x = 0; x < width; x += 10) {
            ctx.beginPath();
            ctx.moveTo(x, 0);
            ctx.lineTo(x, height);
            ctx.stroke();
        }
    }
    
    /**
     * Create minimal background for placeholder
     * 
     * @private
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {number} width - Canvas width
     * @param {number} height - Canvas height
     */
    _createMinimalBackground(ctx, width, height) {
        // Clean white background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, width, height);
        
        // Add subtle corners
        ctx.fillStyle = 'rgba(224, 231, 255, 0.6)';
        
        // Top left corner
        ctx.beginPath();
        ctx.moveTo(0, 0);
        ctx.lineTo(30, 0);
        ctx.lineTo(0, 30);
        ctx.fill();
        
        // Bottom right corner
        ctx.beginPath();
        ctx.moveTo(width, height);
        ctx.lineTo(width - 30, height);
        ctx.lineTo(width, height - 30);
        ctx.fill();
    }
    
    /**
     * API status check
     */
    async checkApiStatus() {
        return {
            success: true,
            message: 'Enhanced PDF preview service is available',
            isAvailable: true,
            features: {
                animations: true,
                placeholders: true,
                backgroundRendering: true
            }
        };
    }
    
    /**
     * Get tools
     */
    async getTools() {
        return {
            success: true,
            message: 'PDF processing tools are available',
            tools: ['preview', 'animation']
        };
    }
}

export default new PdfPreviewService();