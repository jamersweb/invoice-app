import React from 'react';
import { Card } from "@/components/ui/card";
import { Users, TrendingUp } from "lucide-react";

export default function StatsCards({ investments, filteredInvestments }) {
  const totalPrincipal = filteredInvestments.reduce((sum, inv) => sum + inv.amount, 0);
  const uniqueInvestors = [...new Set(filteredInvestments.map(inv => inv.name))].length;
  const averageInvestment = filteredInvestments.length > 0 ? totalPrincipal / filteredInvestments.length : 0;

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  return (
    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <Card className="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-blue-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-slate-400 text-sm font-medium mb-1">Total Principal</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(totalPrincipal)}</p>
              <p className="text-slate-400 text-xs mt-1">AED</p>
            </div>
            <div className="p-3 bg-blue-500/20 rounded-xl">
              <span className="text-2xl font-bold text-blue-400">د.إ</span>
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-purple-800 to-purple-900 border-purple-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-purple-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-purple-200 text-sm font-medium mb-1">Active Investors</p>
              <p className="text-white text-2xl font-bold">{uniqueInvestors}</p>
              <p className="text-purple-200 text-xs mt-1">{filteredInvestments.length} investments</p>
            </div>
            <div className="p-3 bg-purple-500/20 rounded-xl">
              <Users className="w-5 h-5 text-purple-400" />
            </div>
          </div>
        </div>
      </Card>

      <Card className="relative overflow-hidden bg-gradient-to-br from-emerald-800 to-emerald-900 border-emerald-700">
        <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-emerald-500 rounded-full opacity-10" />
        <div className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <p className="text-emerald-200 text-sm font-medium mb-1">Average Investment</p>
              <p className="text-white text-2xl font-bold">{formatCurrency(averageInvestment)}</p>
              <p className="text-emerald-200 text-xs mt-1">AED per transaction</p>
            </div>
            <div className="p-3 bg-emerald-500/20 rounded-xl">
              <TrendingUp className="w-5 h-5 text-emerald-400" />
            </div>
          </div>
        </div>
      </Card>
    </div>
  );
}