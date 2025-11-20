import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Plus, X, Save, AlertCircle } from "lucide-react";
import { motion, AnimatePresence } from "framer-motion";

export default function ExpenseForm({ onSubmit, onCancel, isOpen, editingExpense }) {
  const [formData, setFormData] = useState({
    description: '',
    amount: '',
    expense_category: 'Other',
    status: 'Pending',
    date: '',
    payment_to: '',
    related_transaction_number: '',
    notes: ''
  });

  useEffect(() => {
    if (editingExpense) {
      setFormData({
        description: editingExpense.description,
        amount: editingExpense.amount,
        expense_category: editingExpense.expense_category,
        status: editingExpense.status,
        date: editingExpense.date,
        payment_to: editingExpense.payment_to,
        related_transaction_number: editingExpense.related_transaction_number || '',
        notes: editingExpense.notes || ''
      });
    } else {
      setFormData({
        description: '',
        amount: '',
        expense_category: 'Other',
        status: 'Pending',
        date: '',
        payment_to: '',
        related_transaction_number: '',
        notes: ''
      });
    }
  }, [editingExpense, isOpen]);

  const handleSubmit = (e) => {
    e.preventDefault();
    const submitData = {
      description: formData.description,
      amount: parseFloat(formData.amount),
      expense_category: formData.expense_category,
      status: formData.status,
      date: formData.date,
      payment_to: formData.payment_to,
      notes: formData.notes
    };
    
    if (formData.related_transaction_number) {
      submitData.related_transaction_number = parseInt(formData.related_transaction_number);
    }
    
    onSubmit(submitData);
  };

  const isAdjustmentCategory = formData.expense_category === 'Adjustment';

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
                {editingExpense ? (
                  <>
                    <Save className="w-5 h-5 text-blue-400" />
                    Edit Expense
                  </>
                ) : (
                  <>
                    <Plus className="w-5 h-5 text-blue-400" />
                    Add New Expense
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
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="description" className="text-slate-300">Description</Label>
                  <Input
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder={isAdjustmentCategory ? "e.g., Bank reconciliation adjustment - untracked deposit" : "e.g., Cape payment to ALS or Reconciliation Adjustment"}
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="amount" className="text-slate-300">
                    Amount (AED)
                    {isAdjustmentCategory && <span className="text-emerald-400 ml-1">*</span>}
                  </Label>
                  <Input
                    id="amount"
                    type="number"
                    step="0.01"
                    value={formData.amount}
                    onChange={(e) => setFormData({ ...formData, amount: e.target.value })}
                    placeholder={isAdjustmentCategory ? "e.g., -500 or 1000" : "e.g., 1000"}
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                  {isAdjustmentCategory && (
                    <div className="flex items-start gap-2 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-lg mt-2">
                      <AlertCircle className="w-4 h-4 text-emerald-400 mt-0.5 flex-shrink-0" />
                      <div className="text-xs text-emerald-300">
                        <p className="font-semibold mb-1">Adjustment Guide:</p>
                        <p className="mb-1">• <strong>Negative (-)</strong> = Adds to available balance</p>
                        <p>• <strong>Positive (+)</strong> = Subtracts from available balance</p>
                        <p className="mt-2 text-slate-400">Example: Use -5000 if you found extra funds in your bank account</p>
                      </div>
                    </div>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="date" className="text-slate-300">Date</Label>
                  <Input
                    id="date"
                    type="date"
                    value={formData.date}
                    onChange={(e) => setFormData({ ...formData, date: e.target.value })}
                    required
                    className="bg-slate-900/50 border-slate-700 text-white"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="payment_to" className="text-slate-300">Payment To</Label>
                  <Input
                    id="payment_to"
                    value={formData.payment_to}
                    onChange={(e) => setFormData({ ...formData, payment_to: e.target.value })}
                    placeholder={isAdjustmentCategory ? "e.g., N/A or Reconciliation" : "e.g., ALS, BDFS, Mashreq"}
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="expense_category" className="text-slate-300">Category</Label>
                  <Select
                    value={formData.expense_category}
                    onValueChange={(value) => setFormData({ ...formData, expense_category: value })}
                  >
                    <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent className="bg-slate-800 border-slate-700">
                      <SelectItem value="Pending Payment" className="text-white">Pending Payment</SelectItem>
                      <SelectItem value="IT Fees" className="text-white">IT Fees</SelectItem>
                      <SelectItem value="Transaction Fees" className="text-white">Transaction Fees</SelectItem>
                      <SelectItem value="Bank Charges" className="text-white">Bank Charges</SelectItem>
                      <SelectItem value="Administrative" className="text-white">Administrative</SelectItem>
                      <SelectItem value="Adjustment" className="text-white">Adjustment (Reconciliation)</SelectItem>
                      <SelectItem value="Other" className="text-white">Other</SelectItem>
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
                      <SelectItem value="Pending" className="text-white">Pending</SelectItem>
                      <SelectItem value="Paid" className="text-white">Paid</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="related_transaction_number" className="text-slate-300">Related Transaction # (Optional)</Label>
                  <Input
                    id="related_transaction_number"
                    type="number"
                    value={formData.related_transaction_number}
                    onChange={(e) => setFormData({ ...formData, related_transaction_number: e.target.value })}
                    placeholder="e.g., 11"
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2 md:col-span-3">
                  <Label htmlFor="notes" className="text-slate-300">Notes (Optional)</Label>
                  <Textarea
                    id="notes"
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    placeholder={isAdjustmentCategory ? "Explain the reconciliation reason (e.g., Found unrecorded deposit of 5,000 AED from client X)" : "Additional notes"}
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
                  className="bg-red-600 hover:bg-red-700 text-white"
                >
                  {editingExpense ? (
                    <>
                      <Save className="w-4 h-4 mr-2" />
                      Update Expense
                    </>
                  ) : (
                    <>
                      <Plus className="w-4 h-4 mr-2" />
                      Add Expense
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