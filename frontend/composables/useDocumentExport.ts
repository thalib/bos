/**
 * Document Export Composable
 * Provides reusable functions for exporting documents as PDF, images, copying to clipboard, and printing
 */
import { ref, computed } from 'vue'
import type { DocumentData } from '~/types/document'
import { generateDocumentPdf, previewDocument, getAvailableTemplates } from '~/services/api'

export const useDocumentExport = () => {
  // Loading states
  const pdfExportLoading = ref(false)
  const jpgExportLoading = ref(false)
  const previewLoading = ref(false)

  /**
   * Export document as PDF using backend service
   * @param templateName - Backend template name
   * @param element - HTML element containing document data (optional, used for fallback data extraction)
   * @param filename - Optional filename (defaults to auto-generated)
   * @throws Error if export fails
   */
  const exportAsPdf = async (
    templateName: string, 
    element: HTMLElement,
    filename?: string
  ): Promise<void> => {
    try {
      pdfExportLoading.value = true
      
      // Check authentication first (import from auth service)
      const { useAuthService } = await import('~/services/auth')
      const auth = useAuthService()
      
      if (!auth.isAuthenticated.value) {
        throw new Error('You must be logged in to export documents as PDF')
      }
      
      // Extract document data from the element
      const documentData = extractDocumentData(element)
      
      // Generate PDF using backend service
      const pdfBlob = await generateDocumentPdf(templateName, documentData)
      
      if (!pdfBlob) {
        throw new Error('Failed to generate PDF - empty response')
      }
      
      // Create download link
      const url = URL.createObjectURL(pdfBlob)
      const link = document.createElement('a')
      link.href = url
      link.download = filename || `document-${templateName}-${new Date().toISOString().slice(0, 10)}.pdf`
      link.style.display = 'none'
      
      // Trigger download
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      
      // Clean up
      URL.revokeObjectURL(url)
      
    } catch (error) {
      throw new Error(`PDF export failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    } finally {
      pdfExportLoading.value = false
    }
  }

  /**
   * Generate template preview using backend service
   * @param templateName - Backend template name
   * @param data - Document data to populate the template
   * @returns Promise<string> - HTML preview content
   * @throws Error if preview generation fails
   */
  const previewTemplate = async (
    templateName: string, 
    data: any
  ): Promise<string> => {
    try {
      previewLoading.value = true
      
      const preview = await previewDocument(templateName, data)
      
      if (!preview) {
        throw new Error('Failed to generate preview - empty response')
      }
      
      return preview
      
    } catch (error) {
      throw new Error(`Preview generation failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    } finally {
      previewLoading.value = false
    }
  }

  /**
   * Extract document data from DOM element
   * @param element - HTML element containing document data
   * @returns Extracted document data object
   */
  const extractDocumentData = (element: HTMLElement): any => {
    try {
      // Look for data attributes first
      const dataElement = element.querySelector('[data-document-data]')
      if (dataElement) {
        const dataAttr = dataElement.getAttribute('data-document-data')
        if (dataAttr) {
          return JSON.parse(dataAttr)
        }
      }

      // Fallback: Extract structured data from the DOM
      const extractedData: any = {}

      // Extract text content from common elements
      const titleElement = element.querySelector('h1, h2, .document-title, .title')
      if (titleElement) {
        extractedData.title = titleElement.textContent?.trim()
      }

      // Extract table data
      const tables = element.querySelectorAll('table')
      if (tables.length > 0) {
        extractedData.tables = Array.from(tables).map((table, index) => {
          const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent?.trim())
          const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
            Array.from(tr.querySelectorAll('td')).map(td => td.textContent?.trim())
          )
          return {
            id: `table_${index}`,
            headers,
            rows
          }
        })
      }

      // Extract meta information
      const metaElements = element.querySelectorAll('[data-field]')
      metaElements.forEach(el => {
        const field = el.getAttribute('data-field')
        const value = el.textContent?.trim()
        if (field && value) {
          extractedData[field] = value
        }
      })

      return extractedData

    } catch (error) {
      console.warn('Failed to extract document data from element:', error)
      return {
        title: 'Exported Document',
        content: element.textContent?.trim() || '',
        timestamp: new Date().toISOString()
      }
    }
  }

  /**
   * Export document element as PDF using client-side generation (fallback)
   * @param element - HTML element to export
   * @param filename - Optional filename
   * @throws Error if export fails
   */
  const exportElementAsPdf = async (element: HTMLElement, filename?: string): Promise<void> => {
    try {
      pdfExportLoading.value = true
      
      // For now, fallback to print dialog
      // In the future, this could use jsPDF or other client-side PDF libraries
      console.warn('Client-side PDF generation not available, opening print dialog instead')
      printDocument(element)
      
    } catch (error) {
      throw new Error(`Client-side PDF export failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    } finally {
      pdfExportLoading.value = false
    }
  }
  /**
   * Export document element as JPG image
   * @param element - HTML element to export (should have .document-viewer class)
   * @throws Error if element is not found or export fails
   */
  const exportAsJpg = async (element: HTMLElement): Promise<void> => {
    if (!element) {
      throw new Error('Document element not found')
    }

    try {
      jpgExportLoading.value = true
      
      // Dynamic import for better bundle splitting
      const html2canvas = await import('html2canvas')
      
      const canvas = await html2canvas.default(element, {
        backgroundColor: '#ffffff',
        scale: 2, // Higher quality for better output
        useCORS: true,
        allowTaint: false,
        logging: false, // Disable console logging
        removeContainer: true
      })

      // Convert canvas to blob with high quality
      await new Promise<void>((resolve, reject) => {
        canvas.toBlob((blob) => {
          if (!blob) {
            reject(new Error('Failed to create image blob'))
            return
          }
          
          try {
            // Create download link
            const url = URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = `document-${new Date().toISOString().slice(0, 10)}.jpg`
            link.style.display = 'none'
            
            // Trigger download
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            
            // Clean up
            URL.revokeObjectURL(url)
            resolve()
          } catch (error) {
            reject(new Error(`Download failed: ${error}`))
          }
        }, 'image/jpeg', 0.95) // High quality JPEG
      })
    } catch (error) {
      throw new Error(`JPG export failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    } finally {
      jpgExportLoading.value = false
    }
  }

  /**
   * Copy document element to clipboard as image
   * @param element - HTML element to copy (should have .document-viewer class)
   * @throws Error if element is not found or clipboard operation fails
   */
  const copyToClipboard = async (element: HTMLElement): Promise<void> => {
    if (!element) {
      throw new Error('Document element not found')
    }

    // Check clipboard API support
    if (!navigator.clipboard) {
      throw new Error('Clipboard API not supported in this browser')
    }

    try {
      // Dynamic import for better bundle splitting
      const html2canvas = await import('html2canvas')
      
      const canvas = await html2canvas.default(element, {
        backgroundColor: '#ffffff',
        scale: 2,
        useCORS: true,
        allowTaint: false,
        logging: false,
        removeContainer: true
      })

      // Convert canvas to blob
      await new Promise<void>((resolve, reject) => {
        canvas.toBlob(async (blob) => {
          if (!blob) {
            reject(new Error('Failed to create image blob'))
            return
          }
          
          try {
            // Try modern Clipboard API first
            if (navigator.clipboard && window.ClipboardItem) {
              await navigator.clipboard.write([
                new ClipboardItem({
                  'image/png': blob
                })
              ])
              resolve()
            } else {
              // Fallback: Copy as data URL text
              const dataUrl = canvas.toDataURL('image/png')
              await navigator.clipboard.writeText(dataUrl)
              resolve()
            }
          } catch (clipboardError) {
            reject(new Error(`Clipboard operation failed: ${clipboardError}`))
          }
        }, 'image/png')
      })
    } catch (error) {
      throw new Error(`Clipboard copy failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    }
  }

  /**
   * Print document element in a new window
   * @param element - HTML element to print (should have .document-viewer class)
   * @throws Error if element is not found or print window cannot be opened
   */
  const printDocument = (element: HTMLElement): void => {
    try {
      if (!element) {
        throw new Error('Document element not found')
      }

      // Create print window
      const printWindow = window.open('', '_blank', 'width=800,height=600')
      if (!printWindow) {
        throw new Error('Failed to open print window. Please check popup blocker settings.')
      }

      // Clone element to avoid modifying original
      const clonedElement = element.cloneNode(true) as HTMLElement
      
      // Generate print-optimized HTML
      const printHtml = generatePrintHtml(clonedElement)
      
      // Write content and setup print handlers
      printWindow.document.write(printHtml)
      printWindow.document.close()
      
      // Handle print completion
      printWindow.onbeforeunload = () => {
        printWindow.close()
      }
    } catch (error) {
      throw new Error(`Print failed: ${error instanceof Error ? error.message : 'Unknown error'}`)
    }
  }

  /**
   * Generate print-optimized HTML for document
   * @param element - Cloned document element
   * @returns HTML string for print window
   */
  const generatePrintHtml = (element: HTMLElement): string => {
    // Ensure element has proper styling for print
    element.style.width = '595px'
    element.style.height = '842px'
    element.style.maxWidth = 'none'
    element.style.maxHeight = 'none'
    element.style.background = 'white'
    element.style.border = '1px solid #dee2e6'
    element.style.borderRadius = '0.375rem'
    element.style.display = 'flex'
    element.style.alignItems = 'center'
    element.style.justifyContent = 'center'

    return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Print Document</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background: white;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 20px;
    }
    
    .document-viewer {
      background: white !important;
      box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    }
    
    @media print {
      body {
        padding: 0;
        margin: 0;
        background: white;
      }
      
      .document-viewer {
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        width: 100% !important;
        height: 100vh !important;
        max-width: 100% !important;
        max-height: 100% !important;
      }
      
      @page {
        margin: 0;
        size: A4;
      }
    }
  </style>
</head>
<body>
  ${element.outerHTML}
  <script>
    // Auto-print when window loads
    window.addEventListener('load', function() {
      setTimeout(function() {
        window.print();
      }, 250);
    });
    
    // Close window after printing (or cancel)
    window.addEventListener('afterprint', function() {
      window.close();
    });
    
    // Handle escape key to close
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        window.close();
      }
    });
  </script>
</body>
</html>`
  }

  return {
    // State
    pdfExportLoading: computed(() => pdfExportLoading.value),
    jpgExportLoading: computed(() => jpgExportLoading.value),
    previewLoading: computed(() => previewLoading.value),
    
    // PDF Export methods
    exportAsPdf,
    exportElementAsPdf,
    
    // Template methods
    previewTemplate,
    extractDocumentData,
    
    // Image Export methods
    exportAsJpg,
    copyToClipboard,
    
    // Print method
    printDocument
  }
}
