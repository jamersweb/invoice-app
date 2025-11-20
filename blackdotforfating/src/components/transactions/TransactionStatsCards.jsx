import React from 'react';
import { Card } from "@/components/ui/card";
import { TrendingUp, Clock, CheckCircle } from "lucide-react";

export default function TransactionStatsCards({ transactions, filteredTransactions }) {
  const endedTransactions = filteredTransactions.filter(t => t.status === "Ended");
  const ongoingTransactions = filteredTransactions.filter(t => t.status === "Ongoing");
  
  const totalDisbursedEnded = endedTransactions.reduce((sum, t) => sum + t.net_amount, 0);
  const totalDisbursedOngoing = ongoingTransactions.reduce((sum, t) => sum + t.net_amount, 0);
  const totalDisbursed = totalDisbursedEnded + totalDisbursedOngoing;
  
  const totalProfit = filteredTransactions.reduce((sum, t) => {
    const profit = (t.net_amount * t.profit_margin / 100);
    const netProfit = profit - (t.disbursement_charges || 0);
    return sum + netProfit;
  }, 0);

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <Card className="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-blue-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div className="flex-1">
              <p className="text-slate-400 text-sm font-medium mb-1">Total Disbursed</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(totalDisbursed)}</p>
              <p className="text-slate-400 text-xs mt-1">AED</p>
              <div className="mt-3 pt-3 border-t border-slate-700/50">
                <div className="flex justify-between items-center mb-1">
                  <span className="text-xs text-slate-400">Completed:</span>
                  <span className="text-xs text-green-400 font-medium">{formatCurrency(totalDisbursedEnded)} AED</span>
                </div>
                <div className="flex justify-between items-center">
                  <span className="text-xs text-slate-400">Ongoing:</span>
                  <span className="text-xs text-blue-400 font-medium">{formatCurrency(totalDisbursedOngoing)} AED</span>
                </div>
              </div>
            </div>
            <div className="p-3 bg-blue-500/20 rounded-xl">
              <span className="text-2xl font-bold text-blue-400">د.إ</span>
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-emerald-800 to-emerald-900 border-emerald-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-emerald-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-emerald-200 text-sm font-medium mb-1">Total Net Profit</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(totalProfit)}</p>
              <p className="text-emerald-200 text-xs mt-1">AED (After charges)</p>
            </div>
            <div className="p-3 bg-emerald-500/20 rounded-xl">
              <TrendingUp className="w-5 h-5 text-emerald-400" />
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-amber-800 to-amber-900 border-amber-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-amber-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-amber-200 text-sm font-medium mb-1">Ongoing Deals</p>
              <p className="text-white text-2xl font-bold">{ongoingTransactions.length}</p>
              <p className="text-amber-200 text-xs mt-1">Active transactions</p>
            </div>
            <div className="p-3 bg-amber-500/20 rounded-xl">
              <Clock className="w-5 h-5 text-amber-400" />
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-green-800 to-green-900 border-green-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-green-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-green-200 text-sm font-medium mb-1">Completed Deals</p>
              <p className="text-white text-2xl font-bold">{endedTransactions.length}</p>
              <p className="text-green-200 text-xs mt-1">Successfully closed</p>
            </div>
            <div className="p-3 bg-green-500/20 rounded-xl">
              <CheckCircle className="w-5 h-5 text-green-400" />
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}