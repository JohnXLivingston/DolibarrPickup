import { printPickupLabels, printPickupLineLabels, printProductLabel, setPrintLabelUrl } from './shared/printlabel'

declare global {
  interface Window {
    dolibarrPickupSetPrintLabelUrl: typeof setPrintLabelUrl
    dolibarrPickupPrintProductLabel: typeof printProductLabel
    dolibarrPickupPrintPickupLabels: typeof printPickupLabels
    dolibarrPickupPrintPickupLineLabels: typeof printPickupLineLabels
  }
}
window.dolibarrPickupSetPrintLabelUrl = setPrintLabelUrl
window.dolibarrPickupPrintProductLabel = printProductLabel
window.dolibarrPickupPrintPickupLabels = printPickupLabels
window.dolibarrPickupPrintPickupLineLabels = printPickupLineLabels
