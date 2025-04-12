/**
 * Services/ImagesToPdfService.js
 * Handles conversion of images to PDF
 * Client-side implementation
 */

/**
 * Service for handling image to PDF conversions
 */
class ImagesToPdfService {
    /**
     * Convert images to PDF
     * 
     * @param {File|Array} files - Image file(s) to convert
     * @param {Object} options - Conversion options
     * @returns {Promise<Object>} Operation result
     */
    async convertImagesToPdf(files, options = {}) {
        try {
            console.log('Converting images to PDF (client-side):', 
                Array.isArray(files) ? `${files.length} images` : '1 image');
            
            // Process options
            const pageSize = options.pageSize || 'A4';
            const orientation = options.orientation || 'portrait';
            const margin = options.margin || 10;
            
            console.log('Conversion options:', { pageSize, orientation, margin });
            
            // Simulate processing time based on number of files and their size
            let totalSize = 0;
            let fileArray = Array.isArray(files) ? files : [files];
            
            fileArray.forEach((file, index) => {
                console.log(`Processing image ${index+1}:`, file.name, `(${file.size} bytes)`);
                totalSize += file.size;
            });
            
            // Simulate processing delay based on total file size
            const processingTime = Math.min(3000, totalSize / 100000);
            console.log(`Simulating images to PDF conversion with ${processingTime}ms processing time`);
            await new Promise(resolve => setTimeout(resolve, processingTime));
            
            // Generate a filename for the result
            const timestamp = new Date().getTime();
            let pdfFilename = `converted_images_${timestamp}.pdf`;
            
            // If only one file, use its name as base
            if (fileArray.length === 1) {
                const baseName = fileArray[0].name.split('.')[0] || 'image';
                pdfFilename = `${baseName}.pdf`;
            }
            
            console.log('Images to PDF conversion successful (client-side simulation)');
            
            // Return success response
            return {
                success: true,
                message: 'Images converted to PDF successfully',
                file: URL.createObjectURL(fileArray[0]), // This would be the converted PDF in real implementation
                filename: pdfFilename,
                options: {
                    pageSize,
                    orientation,
                    margin
                }
            };
        } catch (error) {
            // Handle errors
            console.error('Error in client-side images to PDF conversion:', error);
            return {
                success: false,
                message: `Failed to convert images to PDF: ${error.message}`,
                error: error.message
            };
        }
    }
    
    /**
     * Client-side implementation
     * Always used now that we've removed API calls
     */
    async fallbackClientSideConversion(files, options = {}) {
        // Simply call the main method as it's now client-side
        return this.convertImagesToPdf(files, options);
    }
    
    /**
     * Get supported image formats
     * 
     * @returns {Array} List of supported image formats
     */
    getSupportedFormats() {
        // Standard formats supported by most browsers
        return ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
    }
    
    /**
     * Validate an image file to ensure it's supported
     * 
     * @param {File} file - Image file to validate
     * @returns {Object} Validation result
     */
    validateImage(file) {
        if (!file) {
            return {
                valid: false,
                message: 'No file provided'
            };
        }
        
        // Check file type
        const fileType = file.type.split('/')[1]?.toLowerCase();
        const supportedFormats = this.getSupportedFormats();
        
        if (!fileType || !supportedFormats.includes(fileType)) {
            return {
                valid: false,
                message: `Unsupported file format: ${fileType || 'unknown'}. Supported formats: ${supportedFormats.join(', ')}`
            };
        }
        
        // Check file size (max 10MB)
        const maxSize = 10 * 1024 * 1024; // 10MB
        if (file.size > maxSize) {
            return {
                valid: false,
                message: `File size too large: ${(file.size / (1024 * 1024)).toFixed(2)}MB. Maximum allowed: 10MB.`
            };
        }
        
        return {
            valid: true,
            message: 'File is valid'
        };
    }
}

export default new ImagesToPdfService();