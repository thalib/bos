import { useState } from '#app'

// Define the structure of the estimate data
interface Product {
  id: string;
  name: string;
  description: string;
  price: number;
  mrp: number;
  taxPercentage: number;
  unit?: string;
}

interface EstimateItem {
  product: Product;
  quantity: number;
  price: number;
  tax: number;
  total: number;
  unit?: string; // Added unit property to match usage in components
  taxPercentage?: number; // Allow per-item tax percentage override
}

interface Estimate {
  estimateNumber: string;
  date: Date;
  validUntil?: Date; // Optional
  business: {
    name: string;
    address: string;
    contact?: string; // Optional
    gstin?: string; // Optional
  };
  customer: {
    billTo: string;
    notes: string;
  };
  items: EstimateItem[];
  subtotal: number;
  totalTax: number;
  grandTotal: number;
  switches?: {
    incTax?: boolean;
    showSubtotal?: boolean;
    showGst?: boolean;
    showTotal?: boolean;
    freeDelivery?: boolean;
    vrlLogistics?: boolean;
    bankTransfer?: boolean;
  };
  validityDays?: number | string;
  dispatchDays?: string;
  termsAndConditions?: string;
  bankDetails?: string | null;
  documentType?: string; // Added documentType
}

export const useEstimate = () => {
  // Use Nuxt's useState for reactivity and SSR compatibility
  const estimateData = useState<Estimate | null>('estimateData', () => null);
  const showOutput = useState<boolean>('showOutput', () => false);

  const setEstimate = (data: Estimate) => {
    estimateData.value = data;
    showOutput.value = true;
  };

  const clearEstimate = () => {
    estimateData.value = null;
    showOutput.value = false;
  };

  return {
    estimateData,
    showOutput,
    setEstimate,
    clearEstimate
  };
};