# Setting Up Java for PPT to PDF Conversion with Notes

To successfully use the "Include Notes" feature when converting PowerPoint presentations to PDF, you need to make sure Java is properly configured on your system. This guide will walk you through the necessary steps.

## Prerequisites

1. **Java JRE or JDK**: The Java Runtime Environment (JRE) or Java Development Kit (JDK) version 8 or higher must be installed.
2. **LibreOffice**: Must be installed and accessible.
3. **PHP with exec()**: Your PHP installation must have the exec() function enabled.
4. **Proper file permissions**: PHP needs permissions to write to temporary directories.

## Verification Steps

1. **Run the Java setup script**:
   ```
   php setup_java.php
   ```
   This script will check if Java is installed and provide information about its location.

2. **Test Java integration**:
   ```
   php test_java.php
   ```
   This will run a comprehensive test of Java integration with PHP and LibreOffice.

## Configuring Java for XAMPP/Apache

For Java to be accessible by your PHP application, you need to make sure it's in the PATH for Apache:

### Method 1: Use our restart script

1. Run the `restart_xampp.bat` file provided.
2. This script will:
   - Stop Apache
   - Set necessary environment variables for Java
   - Restart Apache with Java in the PATH

### Method 2: Manual configuration (if the script doesn't work)

1. **Add Java to system PATH**:
   - Right-click on 'This PC' and select 'Properties'
   - Click on 'Advanced system settings'
   - Click on 'Environment Variables'
   - Under 'System variables', find and edit 'Path'
   - Add the path to Java bin directory (e.g., `C:\Program Files\Java\jre1.8.0_441\bin`)
   - Click 'OK' on all dialogs

2. **Set Java environment variables**:
   - In the same 'Environment Variables' dialog
   - Add a new system variable named 'JAVA_HOME'
   - Set its value to your Java installation directory (e.g., `C:\Program Files\Java\jre1.8.0_441`)
   - Add another variable named 'JRE_HOME' with the same value
   - Click 'OK' on all dialogs

3. **Restart XAMPP/Apache**:
   - Open XAMPP Control Panel
   - Stop Apache
   - Start Apache

## Testing the Configuration

After completing the setup:

1. Go to the PPT to PDF conversion page in the application
2. Click on the "Check support again" link next to the "Include Notes" option
3. If Java is properly configured, the "Include Notes" option should become available

## Troubleshooting

If you encounter issues:

1. **Java not detected**:
   - Make sure Java is installed
   - Check if Java is in the system PATH
   - Try running `java -version` in a command prompt to verify

2. **Apache/PHP can't access Java**:
   - Make sure Apache has been restarted after adding Java to PATH
   - Check if the exec() function is enabled in php.ini
   - Try running the test_java.php script to diagnose the issue

3. **LibreOffice issues**:
   - Verify LibreOffice is installed and accessible
   - Restart your computer to ensure all changes take effect
   - Check LibreOffice version (5.0 or later recommended)

## Common Errors

1. **"Java not found in PATH"**
   - Solution: Add Java to your PATH environment variable

2. **"Failed to execute process"**
   - Solution: Make sure PHP has permission to execute Java and LibreOffice

3. **"Notes export not supported"**
   - Solution: This typically means Java is not properly configured or detected

For additional help, check the application logs or contact support. 