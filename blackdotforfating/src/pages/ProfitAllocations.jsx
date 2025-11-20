
import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Calculator, TrendingUp, RefreshCw, Download } from "lucide-react";
import { format } from "date-fns";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Link } from "react-router-dom";
import { createPageUrl } from "@/utils";

// Generate consistent hash from string (must match other files)
const generateInvestorHash = (name) => {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    const char = name.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return Math.abs(hash).toString(36);
};

export default function ProfitAllocationsPage() {
  const [isCalculating, setIsCalculating] = useState(false);
  const [isDownloading, setIsDownloading] = useState(false);
  const queryClient = useQueryClient();

  const { data: allocations, isLoading } = useQuery({
    queryKey: ['profitAllocations'],
    queryFn: () => base44.entities.ProfitAllocation.list('-transaction_number'),
    initialData: [],
  });

  const { data: investments } = useQuery({
    queryKey: ['investments'],
    queryFn: () => base44.entities.Investment.list(),
    initialData: [],
  });

  const { data: users } = useQuery({
    queryKey: ['users'],
    queryFn: () => base44.entities.User.list(),
    initialData: [],
  });

  const calculateAllocations = async () => {
    setIsCalculating(true);
    
    try {
      // Call the backend function to recalculate allocations
      const response = await base44.functions.invoke('recalculateProfitAllocations', {});
      
      if (response.data.success) {
        console.log('Allocations recalculated:', response.data);
        queryClient.invalidateQueries({ queryKey: ['profitAllocations'] });
      } else {
        console.error('Error recalculating allocations:', response.data.error);
        alert('Error recalculating allocations. Please try again.');
      }
    } catch (error) {
      console.error("Error calling recalculation function:", error);
      alert('Error recalculating allocations. Please try again.');
    } finally {
      setIsCalculating(false);
    }
  };

  const handleDownloadExcel = async () => {
    setIsDownloading(true);
    try {
      const response = await base44.functions.invoke('exportToExcel', { entityName: 'ProfitAllocation' });
      
      // Decode base64 string to binary
      const binaryString = atob(response.data.data);
      const bytes = new Uint8Array(binaryString.length);
      for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
      }
      
      const blob = new Blob([bytes], { 
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = response.data.filename || 'profit_allocations.xlsx';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
    } catch (error) {
      console.error('Download error:', error);
      alert('Error downloading Excel file. Please try again.');
    } finally {
      setIsDownloading(false);
    }
  };

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const formatDate = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy');
  };

  const formatPercentage = (value) => {
    return (value * 100).toFixed(1) + '%';
  };

  // Group allocations by investor with split between realized and expected
  const investorSummary = {};
  allocations.forEach(alloc => {
    if (!investorSummary[alloc.investor_name]) {
      investorSummary[alloc.investor_name] = {
        realizedProfit: 0,
        expectedProfit: 0,
        totalProfit: 0,
        dealsCount: 0
      };
    }
    
    if (alloc.deal_status === "Ended") {
      investorSummary[alloc.investor_name].realizedProfit += alloc.individual_profit;
    } else if (alloc.deal_status === "Ongoing") {
      investorSummary[alloc.investor_name].expectedProfit += alloc.individual_profit;
    }
    
    investorSummary[alloc.investor_name].totalProfit += alloc.individual_profit;
    investorSummary[alloc.investor_name].dealsCount += 1;
  });

  // Calculate capital and principal for each investor
  Object.keys(investorSummary).forEach(investor => {
    const principalSum = investments
      .filter(inv => inv.name === investor)
      .reduce((sum, inv) => sum + inv.amount, 0);
    
    investorSummary[investor].principal = principalSum;
    investorSummary[investor].capital = principalSum + investorSummary[investor].realizedProfit;
  });

  // Helper function to get investor_id from investor name
  const getInvestorId = (investorName) => {
    const user = users.find(u => 
      (u.investor_name === investorName || u.full_name === investorName) && u.investor_id
    );
    return user?.investor_id || generateInvestorHash(investorName);
  };

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-3 bg-purple-600/20 rounded-xl">
                <Calculator className="w-8 h-8 text-purple-400" />
              </div>
              <div>
                <h2 className="text-3xl font-bold text-white">Profit Allocations</h2>
                <p className="text-slate-400 mt-1">Calculate and track investor profit distributions</p>
              </div>
            </div>

            <div className="flex gap-3">
              <Button
                onClick={handleDownloadExcel}
                disabled={isDownloading}
                variant="outline"
                className="border-purple-600 text-purple-400 hover:bg-purple-600/10"
              >
                {isDownloading ? (
                  <>
                    <Download className="w-4 h-4 mr-2 animate-bounce" />
                    Downloading...
                  </>
                ) : (
                  <>
                    <Download className="w-4 h-4 mr-2" />
                    Download Excel
                  </>
                )}
              </Button>
              <Button
                onClick={calculateAllocations}
                disabled={isCalculating}
                className="bg-purple-600 hover:bg-purple-700 text-white shadow-lg shadow-purple-600/20"
              >
                {isCalculating ? (
                  <>
                    <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
                    Calculating...
                  </>
                ) : (
                  <>
                    <Calculator className="w-4 h-4 mr-2" />
                    Calculate Allocations
                  </>
                )}
              </Button>
            </div>
          </div>
        </div>

        {/* Investor Summary Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
          {Object.entries(investorSummary).map(([investor, summary]) => {
            const investorId = getInvestorId(investor);
            const linkUrl = createPageUrl('InvestorDashboard') + '?id=' + investorId;
            
            return (
              <Link 
                key={investor} 
                to={linkUrl}
                className="block transition-transform hover:scale-105"
              >
                <Card className="bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700 p-4 cursor-pointer hover:border-blue-500/50 transition-colors">
                  <div className="flex items-center justify-between mb-2">
                    <p className="text-slate-300 font-semibold">{investor}</p>
                    <TrendingUp className="w-4 h-4 text-emerald-400" />
                  </div>
                  <p className="text-white text-xl font-bold">{formatCurrency(summary.totalProfit)}</p>
                  <p className="text-slate-400 text-xs mt-1">Total Profit (AED)</p>
                  <div className="mt-3 pt-3 border-t border-slate-700/50 space-y-1">
                    <div className="flex justify-between text-xs">
                      <span className="text-slate-400">Realized:</span>
                      <span className="text-green-400">{formatCurrency(summary.realizedProfit)}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                      <span className="text-slate-400">Expected:</span>
                      <span className="text-amber-400">{formatCurrency(summary.expectedProfit)}</span>
                    </div>
                    <div className="flex justify-between text-xs pt-1 border-t border-slate-700/30">
                      <span className="text-slate-400">Capital:</span>
                      <span className="text-blue-400">{formatCurrency(summary.capital)}</span>
                    </div>
                    <div className="flex justify-between text-xs">
                      <span className="text-slate-400">Deals:</span>
                      <span className="text-purple-400">{summary.dealsCount}</span>
                    </div>
                  </div>
                </Card>
              </Link>
            );
          })}
        </div>

        {/* Allocations Table */}
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500"></div>
          </div>
        ) : allocations.length === 0 ? (
          <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
            <Calculator className="w-16 h-16 text-slate-600 mx-auto mb-4" />
            <p className="text-slate-400 text-lg mb-2">No allocations calculated yet</p>
            <p className="text-slate-500 text-sm mb-6">Click "Calculate Allocations" to generate profit distributions</p>
          </Card>
        ) : (
          <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                    <TableHead className="text-slate-300 font-semibold">#</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Investor</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-right">Amount</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Date</TableHead>
                    <TableHead className="text-slate-300 font-semibold">End Date</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-center">Weightage</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-right">Deal Profit</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-right">Individual Profit</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-right">Invested Capital</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-center">Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {allocations.map((allocation) => (
                    <TableRow key={allocation.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                      <TableCell className="font-medium text-white">{allocation.transaction_number}</TableCell>
                      <TableCell className="text-white font-medium">{allocation.investor_name}</TableCell>
                      <TableCell className="text-right font-mono text-blue-400">
                        {formatCurrency(allocation.transaction_amount)}
                      </TableCell>
                      <TableCell className="text-slate-300">{formatDate(allocation.transaction_date)}</TableCell>
                      <TableCell className="text-slate-300">{formatDate(allocation.deal_end_date)}</TableCell>
                      <TableCell className="text-center text-amber-400 font-semibold">
                        {formatPercentage(allocation.weightage)}
                      </TableCell>
                      <TableCell className="text-right font-mono text-emerald-400">
                        {formatCurrency(allocation.deal_profit)}
                      </TableCell>
                      <TableCell className="text-right font-mono text-emerald-400 font-semibold">
                        {formatCurrency(allocation.individual_profit)}
                      </TableCell>
                      <TableCell className="text-right font-mono text-purple-400">
                        {formatCurrency(allocation.invested_capital)}
                      </TableCell>
                      <TableCell className="text-center">
                        {allocation.deal_status === "Ended" ? (
                          <Badge className="bg-green-500/20 text-green-400 border-green-500/50">Ended</Badge>
                        ) : allocation.deal_status === "Ongoing" ? (
                          <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">Ongoing</Badge>
                        ) : (
                          <Badge className="bg-gray-500/20 text-gray-400 border-gray-500/50">Not Disbursed</Badge>
                        )}
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
