import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Plus, X, Save, Calculator } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";

export default function TransactionForm({ onSubmit, onCancel, isOpen, editingTransaction }) {
  const [inputMode, setInputMode] = useState('direct'); // 'direct' or 'per_annum'
  const [formData, setFormData] = useState({
    transaction_number: '',
    date_of_transaction: '',
    net_amount: '',
    profit_margin: '',
    sales_cycle: '',
    customer: '',
    additional_comments: '',
    disbursement_charges: '0',
    disbursed_from: 'BDFS',
    status: 'Ongoing'
  });
  const [perAnnumProfit, setPerAnnumProfit] = useState('');

  // Fetch customers for dropdown
  const { data: customers } = useQuery({
    queryKey: ['customers'],
    queryFn: () => base44.entities.Customer.list('name'),
    initialData: [],
  });

  useEffect(() => {
    if (editingTransaction) {
      setFormData({
        transaction_number: editingTransaction.transaction_number,
        date_of_transaction: editingTransaction.date_of_transaction,
        net_amount: editingTransaction.net_amount,
        profit_margin: editingTransaction.profit_margin,
        sales_cycle: editingTransaction.sales_cycle,
        customer: editingTransaction.customer,
        additional_comments: editingTransaction.additional_comments || '',
        disbursement_charges: editingTransaction.disbursement_charges || '0',
        disbursed_from: editingTransaction.disbursed_from,
        status: editingTransaction.status
      });
      // Reset to direct mode when editing
      setInputMode('direct');
      setPerAnnumProfit('');
    } else {
      setFormData({
        transaction_number: '',
        date_of_transaction: '',
        net_amount: '',
        profit_margin: '',
        sales_cycle: '',
        customer: '',
        additional_comments: '',
        disbursement_charges: '0',
        disbursed_from: 'BDFS',
        status: 'Ongoing'
      });
      setPerAnnumProfit('');
    }
  }, [editingTransaction, isOpen]);

  // Auto-calculate profit_margin from per annum % when sales_cycle or perAnnumProfit changes
  useEffect(() => {
    if (inputMode === 'per_annum' && perAnnumProfit && formData.sales_cycle) {
      const perAnnumValue = parseFloat(perAnnumProfit);
      const salesCycleValue = parseInt(formData.sales_cycle);
      
      if (!isNaN(perAnnumValue) && !isNaN(salesCycleValue) && salesCycleValue > 0) {
        // Formula: (per_annum / 360) * sales_cycle = profit_margin
        const calculatedMargin = (perAnnumValue / 360) * salesCycleValue;
        setFormData(prev => ({ ...prev, profit_margin: calculatedMargin.toFixed(4) }));
      }
    }
  }, [perAnnumProfit, formData.sales_cycle, inputMode]);

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit({
      transaction_number: parseInt(formData.transaction_number),
      date_of_transaction: formData.date_of_transaction,
      net_amount: parseFloat(formData.net_amount),
      profit_margin: parseFloat(formData.profit_margin),
      sales_cycle: parseInt(formData.sales_cycle),
      customer: formData.customer,
      additional_comments: formData.additional_comments,
      disbursement_charges: parseFloat(formData.disbursement_charges),
      disbursed_from: formData.disbursed_from,
      status: formData.status
    });
  };

  const toggleInputMode = () => {
    if (inputMode === 'direct') {
      setInputMode('per_annum');
      setPerAnnumProfit('');
      setFormData(prev => ({ ...prev, profit_margin: '' }));
    } else {
      setInputMode('direct');
      setPerAnnumProfit('');
    }
  };

  if (!isOpen) return null;

  return (
    <AnimatePresence>
      <motion.div
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        exit={{ opacity: 0, y: -20 }}
        transition={{ duration: 0.3 }}
      >
        <Card className="mb-6 bg-slate-800/50 border-slate-700 backdrop-blur-sm">
          <CardHeader className="border-b border-slate-700">
            <div className="flex justify-between items-center">
              <CardTitle className="text-white flex items-center gap-2">
                {editingTransaction ? (
                  <>
                    <Save className="w-5 h-5 text-blue-400" />
                    Edit Transaction
                  </>
                ) : (
                  <>
                    <Plus className="w-5 h-5 text-blue-400" />
                    Add New Transaction
                  </>
                )}
              </CardTitle>
              <Button
                variant="ghost"
                size="icon"
                onClick={onCancel}
                className="text-slate-400 hover:text-white"
              >
                <X className="w-4 h-4" />
              </Button>
            </div>
          </CardHeader>
          <CardContent className="pt-6">
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="transaction_number" className="text-slate-300">Transaction #</Label>
                  <Input
                    id="transaction_number"
                    type="number"
                    value={formData.transaction_number}
                    onChange={(e) => setFormData({ ...formData, transaction_number: e.target.value })}
                    placeholder="e.g., 1"
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="date_of_transaction" className="text-slate-300">Transaction Date</Label>
                  <Input
                    id="date_of_transaction"
                    type="date"
                    value={formData.date_of_transaction}
                    onChange={(e) => setFormData({ ...formData, date_of_transaction: e.target.value })}
                    required
                    className="bg-slate-900/50 border-slate-700 text-white"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="customer" className="text-slate-300">Customer</Label>
                  <Select
                    value={formData.customer}
                    onValueChange={(value) => setFormData({ ...formData, customer: value })}
                  >
                    <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                      <SelectValue placeholder="Select customer..." />
                    </SelectTrigger>
                    <SelectContent className="bg-slate-800 border-slate-700">
                      {customers.map((customer) => (
                        <SelectItem key={customer.id} value={customer.name} className="text-white">
                          {customer.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="net_amount" className="text-slate-300">Net Amount (AED)</Label>
                  <Input
                    id="net_amount"
                    type="number"
                    step="0.01"
                    value={formData.net_amount}
                    onChange={(e) => setFormData({ ...formData, net_amount: e.target.value })}
                    placeholder="e.g., 60000"
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="sales_cycle" className="text-slate-300">Sales Cycle (days)</Label>
                  <Input
                    id="sales_cycle"
                    type="number"
                    value={formData.sales_cycle}
                    onChange={(e) => setFormData({ ...formData, sales_cycle: e.target.value })}
                    placeholder="e.g., 45"
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                {/* Profit Input Section with Toggle */}
                <div className="space-y-2 md:col-span-1">
                  <div className="flex items-center justify-between">
                    <Label className="text-slate-300">
                      {inputMode === 'direct' ? 'Profit Margin (%)' : 'Per Annum Profit (%)'}
                    </Label>
                    <Button
                      type="button"
                      variant="ghost"
                      size="sm"
                      onClick={toggleInputMode}
                      className="text-xs text-blue-400 hover:text-blue-300 h-6 px-2"
                    >
                      <Calculator className="w-3 h-3 mr-1" />
                      {inputMode === 'direct' ? 'Use Per Annum' : 'Direct Input'}
                    </Button>
                  </div>
                  
                  {inputMode === 'direct' ? (
                    <Input
                      id="profit_margin"
                      type="number"
                      step="0.01"
                      value={formData.profit_margin}
                      onChange={(e) => setFormData({ ...formData, profit_margin: e.target.value })}
                      placeholder="e.g., 5.0"
                      required
                      className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                    />
                  ) : (
                    <div className="space-y-2">
                      <Input
                        id="per_annum_profit"
                        type="number"
                        step="0.01"
                        value={perAnnumProfit}
                        onChange={(e) => setPerAnnumProfit(e.target.value)}
                        placeholder="e.g., 12.0"
                        required
                        className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                      />
                      {formData.profit_margin && (
                        <p className="text-xs text-emerald-400 flex items-center gap-1">
                          <Calculator className="w-3 h-3" />
                          Calculated Margin: {parseFloat(formData.profit_margin).toFixed(2)}%
                        </p>
                      )}
                    </div>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="disbursement_charges" className="text-slate-300">Disbursement Charges (AED)</Label>
                  <Input
                    id="disbursement_charges"
                    type="number"
                    step="0.01"
                    value={formData.disbursement_charges}
                    onChange={(e) => setFormData({ ...formData, disbursement_charges: e.target.value })}
                    placeholder="e.g., 0"
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="disbursed_from" className="text-slate-300">Disbursed From</Label>
                  <Select
                    value={formData.disbursed_from}
                    onValueChange={(value) => setFormData({ ...formData, disbursed_from: value })}
                  >
                    <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-slate-800 border-slate-700">
                      <SelectItem value="ALS" className="text-white">ALS</SelectItem>
                      <SelectItem value="BDFS" className="text-white">BDFS</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="status" className="text-slate-300">Status</Label>
                  <Select
                    value={formData.status}
                    onValueChange={(value) => setFormData({ ...formData, status: value })}
                  >
                    <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-slate-800 border-slate-700">
                      <SelectItem value="Not Disbursed" className="text-white">Not Disbursed</SelectItem>
                      <SelectItem value="Ongoing" className="text-white">Ongoing</SelectItem>
                      <SelectItem value="Ended" className="text-white">Ended</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2 md:col-span-3">
                  <Label htmlFor="additional_comments" className="text-slate-300">Additional Comments</Label>
                  <Textarea
                    id="additional_comments"
                    value={formData.additional_comments}
                    onChange={(e) => setFormData({ ...formData, additional_comments: e.target.value })}
                    placeholder="Optional notes..."
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>
              </div>
              
              <div className="flex justify-end gap-3 pt-4">
                <Button
                  type="button"
                  variant="outline"
                  onClick={onCancel}
                  className="bg-slate-900/50 border-slate-700 text-white hover:bg-slate-800"
                >
                  Cancel
                </Button>
                <Button
                  type="submit"
                  className="bg-blue-600 hover:bg-blue-700 text-white"
                >
                  {editingTransaction ? (
                    <>
                      <Save className="w-4 h-4 mr-2" />
                      Update Transaction
                    </>
                  ) : (
                    <>
                      <Plus className="w-4 h-4 mr-2" />
                      Add Transaction
                    </>
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </motion.div>
    </AnimatePresence>
  );
}