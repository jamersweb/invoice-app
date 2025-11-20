import React from 'react';
import { Card } from "@/components/ui/card";
import { AlertCircle, CheckCircle, DollarSign } from "lucide-react";

export default function ExpenseStatsCards({ expenses, filteredExpenses }) {
  const pendingExpenses = filteredExpenses.filter(e => e.status === "Pending");
  const paidExpenses = filteredExpenses.filter(e => e.status === "Paid");
  
  const totalPending = pendingExpenses.reduce((sum, e) => sum + e.amount, 0);
  const totalPaid = paidExpenses.reduce((sum, e) => sum + e.amount, 0);
  const totalExpenses = totalPending + totalPaid;

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <Card className="relative overflow-hidden bg-gradient-to-br from-red-800 to-red-900 border-red-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-red-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-red-200 text-sm font-medium mb-1">Pending Expenses</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(totalPending)}</p>
              <p className="text-red-200 text-xs mt-1">{pendingExpenses.length} pending items</p>
            </div>
            <div className="p-3 bg-red-500/20 rounded-xl">
              <AlertCircle className="w-5 h-5 text-red-400" />
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-green-800 to-green-900 border-green-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-green-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-green-200 text-sm font-medium mb-1">Paid Expenses</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(totalPaid)}</p>
              <p className="text-green-200 text-xs mt-1">{paidExpenses.length} paid items</p>
            </div>
            <div className="p-3 bg-green-500/20 rounded-xl">
              <CheckCircle className="w-5 h-5 text-green-400" />
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-blue-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-slate-300 text-sm font-medium mb-1">Total Expenses</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(totalExpenses)}</p>
              <p className="text-slate-400 text-xs mt-1">AED</p>
            </div>
            <div className="p-3 bg-blue-500/20 rounded-xl">
              <DollarSign className="w-5 h-5 text-blue-400" />
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}