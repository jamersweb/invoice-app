import React, { useMemo, useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { BarChart3, TrendingUp, DollarSign, Activity, Wallet, Calendar, ArrowUpDown } from "lucide-react";
import { format, addDays, differenceInDays } from "date-fns";

export default function DashboardPage() {
  const [customerSortBy, setCustomerSortBy] = useState('perAnnumProfitPercent');
  const [customerSortOrder, setCustomerSortOrder] = useState('desc');

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const formatNumber = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value);
  };

  const formatPercent = (value) => {
    return value.toFixed(2) + '%';
  };

  const formatDateMMYY = (dateString) => {
    return format(new Date(dateString), 'MM/yy');
  };

  const { data: investments, isLoading: investmentsLoading } = useQuery({
    queryKey: ['investments'],
    queryFn: () => base44.entities.Investment.list(),
    initialData: [],
  });

  const { data: transactions, isLoading: transactionsLoading } = useQuery({
    queryKey: ['transactions'],
    queryFn: () => base44.entities.Transaction.list(),
    initialData: [],
  });

  const { data: allocations, isLoading: allocationsLoading } = useQuery({
    queryKey: ['profitAllocations'],
    queryFn: () => base44.entities.ProfitAllocation.list(),
    initialData: [],
  });

  const { data: expenses, isLoading: expensesLoading } = useQuery({
    queryKey: ['expenses'],
    queryFn: () => base44.entities.Expense.list(),
    initialData: [],
  });

  const USD_RATE = 3.67;

  const metrics = useMemo(() => {
    const totalPrincipal = investments.reduce((sum, inv) => sum + inv.amount, 0);
    
    const realizedProfit = allocations
      .filter(a => a.deal_status === "Ended")
      .reduce((sum, a) => sum + a.individual_profit, 0);
    
    const pendingProfit = allocations
      .filter(a => a.deal_status === "Ongoing")
      .reduce((sum, a) => sum + a.individual_profit, 0);
    
    const totalFund = totalPrincipal + realizedProfit;
    
    const lockedAmount = transactions
      .filter(t => t.status === "Ongoing")
      .reduce((sum, t) => sum + t.net_amount, 0);
    
    const pendingExpenses = expenses
      .filter(e => e.status === "Pending")
      .reduce((sum, e) => sum + e.amount, 0);
    
    const availableBalance = totalFund - lockedAmount - pendingExpenses;
    
    const completedTransactions = transactions.filter(t => t.status === "Ended");
    
    const completedDisbursements = completedTransactions.reduce((sum, t) => sum + t.net_amount, 0);
    
    const totalNetAmountDisbursed = transactions.reduce((sum, t) => sum + t.net_amount, 0);
    const totalNetProfit = transactions.reduce((sum, t) => {
      const profit = (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0);
      return sum + profit;
    }, 0);
    
    const globalProfitabilityPercent = totalNetAmountDisbursed > 0 
      ? (totalNetProfit / totalNetAmountDisbursed) * 100 
      : 0;
    
    const avgTenure = transactions.length > 0 
      ? transactions.reduce((sum, t) => sum + t.sales_cycle, 0) / transactions.length 
      : 0;
    
    const perDayProfit = avgTenure > 0 ? (globalProfitabilityPercent / avgTenure) : 0;
    const perAnnumProfit = perDayProfit * 360;
    const unutilizedPercent = totalFund > 0 ? (availableBalance / totalFund) * 100 : 0;

    return {
      totalPrincipal,
      realizedProfit,
      pendingProfit,
      totalFund,
      lockedAmount,
      pendingExpenses,
      availableBalance,
      completedDisbursements,
      avgTenure,
      perDayProfit,
      perAnnumProfit,
      unutilizedPercent
    };
  }, [investments, transactions, allocations, expenses]);

  const upcomingDeals = useMemo(() => {
    return transactions
      .filter(t => t.status === "Ongoing")
      .map(t => {
        const endDate = addDays(new Date(t.date_of_transaction), t.sales_cycle);
        const daysRemaining = differenceInDays(endDate, new Date());
        const expectedProfit = (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0);
        
        return {
          transaction_number: t.transaction_number,
          customer: t.customer,
          disbursementDate: new Date(t.date_of_transaction),
          endDate: endDate,
          daysRemaining: daysRemaining,
          amount: t.net_amount,
          expectedProfit: expectedProfit,
          profitMargin: t.profit_margin
        };
      })
      .sort((a, b) => a.endDate - b.endDate);
  }, [transactions]);

  const investorSummary = useMemo(() => {
    const summary = {};
    
    investments.forEach(inv => {
      if (!summary[inv.name]) {
        summary[inv.name] = { principal: 0, realized: 0, pending: 0 };
      }
      summary[inv.name].principal += inv.amount;
    });
    
    allocations.forEach(alloc => {
      if (!summary[alloc.investor_name]) {
        summary[alloc.investor_name] = { principal: 0, realized: 0, pending: 0 };
      }
      if (alloc.deal_status === "Ended") {
        summary[alloc.investor_name].realized += alloc.individual_profit;
      } else if (alloc.deal_status === "Ongoing") {
        summary[alloc.investor_name].pending += alloc.individual_profit;
      }
    });
    
    return Object.entries(summary).map(([name, data]) => ({
      name,
      principal: data.principal,
      profitActual: data.realized,
      profitWithPending: data.realized + data.pending,
      totalActual: data.principal + data.realized,
      totalWithPending: data.principal + data.realized + data.pending,
      profitPercentActual: data.principal > 0 ? (data.realized / data.principal) * 100 : 0,
      profitPercentWithPending: data.principal > 0 ? ((data.realized + data.pending) / data.principal) * 100 : 0
    }));
  }, [investments, allocations]);

  const customerAnalysis = useMemo(() => {
    const customers = {};
    
    transactions.forEach(t => {
      if (!customers[t.customer]) {
        customers[t.customer] = {
          total: 0,
          ended: 0,
          ongoing: 0,
          totalDisbursed: 0,
          totalGross: 0,
          netProfit: 0,
          totalSalesCycle: 0
        };
      }
      
      customers[t.customer].total += 1;
      if (t.status === "Ended") customers[t.customer].ended += 1;
      if (t.status === "Ongoing") customers[t.customer].ongoing += 1;
      
      customers[t.customer].totalDisbursed += t.net_amount;
      const gross = t.net_amount + (t.net_amount * t.profit_margin / 100);
      customers[t.customer].totalGross += gross;
      
      const profit = (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0);
      customers[t.customer].netProfit += profit;
      
      customers[t.customer].totalSalesCycle += t.sales_cycle;
    });
    
    const totalProfit = Object.values(customers).reduce((sum, c) => sum + c.netProfit, 0);
    const totalDisbursed = Object.values(customers).reduce((sum, c) => sum + c.totalDisbursed, 0);
    
    return Object.entries(customers).map(([name, data]) => {
      const avgCustomerTenor = data.total > 0 ? data.totalSalesCycle / data.total : 0;
      const profitPercent = data.totalDisbursed > 0 ? (data.netProfit / data.totalDisbursed) * 100 : 0;
      const perAnnumProfitPercent = avgCustomerTenor > 0 ? profitPercent * (360 / avgCustomerTenor) : 0;
      const riskAdjustedYield = avgCustomerTenor > 0 ? profitPercent / avgCustomerTenor : 0;
      
      return {
        name,
        ...data,
        avgCustomerTenor,
        profitPercent,
        perAnnumProfitPercent,
        riskAdjustedYield,
        shareOfProfit: totalProfit > 0 ? (data.netProfit / totalProfit) * 100 : 0,
        shareOfDisbursements: totalDisbursed > 0 ? (data.totalDisbursed / totalDisbursed) * 100 : 0,
        avgTicketSize: data.total > 0 ? data.totalDisbursed / data.total : 0
      };
    });
  }, [transactions]);

  const sortedCustomerAnalysis = useMemo(() => {
    return [...customerAnalysis].sort((a, b) => {
      const multiplier = customerSortOrder === 'asc' ? 1 : -1;
      return (a[customerSortBy] - b[customerSortBy]) * multiplier;
    });
  }, [customerAnalysis, customerSortBy, customerSortOrder]);

  const handleCustomerSort = (field) => {
    if (customerSortBy === field) {
      setCustomerSortOrder(customerSortOrder === 'asc' ? 'desc' : 'asc');
    } else {
      setCustomerSortBy(field);
      setCustomerSortOrder('desc');
    }
  };

  const isLoading = investmentsLoading || transactionsLoading || allocationsLoading || expensesLoading;

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  const totalPrincipalShare = investorSummary.reduce((sum, inv) => sum + inv.principal, 0);
  const totalActualShare = investorSummary.reduce((sum, inv) => sum + inv.totalActual, 0);
  const totalWithPendingShare = investorSummary.reduce((sum, inv) => sum + inv.totalWithPending, 0);

  return (
    <div className="p-3 sm:p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-6 sm:mb-8">
          <div className="flex items-center gap-2 sm:gap-3 mb-2">
            <div className="p-2 sm:p-3 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl">
              <BarChart3 className="w-6 h-6 sm:w-8 sm:h-8 text-white" />
            </div>
            <div>
              <h2 className="text-2xl sm:text-3xl font-bold text-white">Global Dashboard</h2>
              <p className="text-slate-400 mt-1 text-xs sm:text-base hidden sm:block">Comprehensive overview of all metrics and performance</p>
            </div>
          </div>
        </div>

        {/* Key Metrics Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 sm:mb-8">
          <Card className="bg-gradient-to-br from-blue-800 to-blue-900 border-blue-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-blue-200 text-xs font-medium mb-1">Total Principal</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatCurrency(metrics.totalPrincipal)}</p>
                <p className="text-blue-300 text-[10px] sm:text-xs mt-1">USD {formatCurrency(metrics.totalPrincipal / USD_RATE)}</p>
              </div>
              <Wallet className="w-4 h-4 sm:w-5 sm:h-5 text-blue-400" />
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-emerald-800 to-emerald-900 border-emerald-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-emerald-200 text-xs font-medium mb-1">Total Net Profit</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatCurrency(metrics.realizedProfit)}</p>
                <p className="text-emerald-300 text-[10px] sm:text-xs mt-1">USD {formatCurrency(metrics.realizedProfit / USD_RATE)}</p>
              </div>
              <TrendingUp className="w-4 h-4 sm:w-5 sm:h-5 text-emerald-400" />
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-purple-800 to-purple-900 border-purple-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-purple-200 text-xs font-medium mb-1">Total Fund</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatCurrency(metrics.totalFund)}</p>
                <p className="text-purple-300 text-[10px] sm:text-xs mt-1">USD {formatCurrency(metrics.totalFund / USD_RATE)}</p>
              </div>
              <DollarSign className="w-4 h-4 sm:w-5 sm:h-5 text-purple-400" />
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-amber-800 to-amber-900 border-amber-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-amber-200 text-xs font-medium mb-1">Pending Profit</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatCurrency(metrics.pendingProfit)}</p>
                <p className="text-amber-300 text-[10px] sm:text-xs mt-1">USD {formatCurrency(metrics.pendingProfit / USD_RATE)}</p>
              </div>
              <Activity className="w-4 h-4 sm:w-5 sm:h-5 text-amber-400" />
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-slate-300 text-xs font-medium mb-1">Locked Amount</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatNumber(metrics.lockedAmount)}</p>
                <p className="text-slate-400 text-[10px] sm:text-xs mt-1">USD {formatNumber(metrics.lockedAmount / USD_RATE)}</p>
              </div>
              <span className="text-lg sm:text-xl">🔒</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-red-800 to-red-900 border-red-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-red-200 text-xs font-medium mb-1">Pending Expenses</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatNumber(metrics.pendingExpenses)}</p>
                <p className="text-red-300 text-[10px] sm:text-xs mt-1">USD {formatNumber(metrics.pendingExpenses / USD_RATE)}</p>
              </div>
              <span className="text-lg sm:text-xl">⚠️</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-green-800 to-green-900 border-green-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-green-200 text-xs font-medium mb-1">Available Balance</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatCurrency(metrics.availableBalance)}</p>
                <p className="text-green-300 text-[10px] sm:text-xs mt-1">USD {formatCurrency(metrics.availableBalance / USD_RATE)}</p>
              </div>
              <span className="text-lg sm:text-xl">💰</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-pink-800 to-pink-900 border-pink-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-pink-200 text-xs font-medium mb-1">Average Tenure</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatNumber(metrics.avgTenure)}</p>
                <p className="text-pink-300 text-[10px] sm:text-xs mt-1">Days</p>
              </div>
              <span className="text-lg sm:text-xl">📅</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-cyan-800 to-cyan-900 border-cyan-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-cyan-200 text-xs font-medium mb-1">Per Day Profit</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatPercent(metrics.perDayProfit)}</p>
                <p className="text-cyan-300 text-[10px] sm:text-xs mt-1 hidden sm:block">Net / Disbursed / Tenor</p>
              </div>
              <span className="text-lg sm:text-xl">📊</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-orange-800 to-orange-900 border-orange-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-orange-200 text-xs font-medium mb-1">Per Annum</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatPercent(metrics.perAnnumProfit)}</p>
                <p className="text-orange-300 text-[10px] sm:text-xs mt-1">Per Day × 360</p>
              </div>
              <span className="text-lg sm:text-xl">📈</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-red-800 to-red-900 border-red-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-red-200 text-xs font-medium mb-1">Un-utilized %</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatPercent(metrics.unutilizedPercent)}</p>
                <p className="text-red-300 text-[10px] sm:text-xs mt-1">Idle funds</p>
              </div>
              <span className="text-lg sm:text-xl">⚠️</span>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-teal-800 to-teal-900 border-teal-700 p-3 sm:p-4">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-teal-200 text-xs font-medium mb-1">Disbursed</p>
                <p className="text-white text-lg sm:text-xl font-bold">{formatNumber(metrics.completedDisbursements)}</p>
                <p className="text-teal-300 text-[10px] sm:text-xs mt-1">Ended deals</p>
              </div>
              <span className="text-lg sm:text-xl">✅</span>
            </div>
          </Card>
        </div>

        {/* Upcoming Deals Table */}
        <Card className="bg-slate-800/30 border-slate-700 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-4">
            <Calendar className="w-4 h-4 sm:w-5 sm:h-5 text-blue-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Upcoming Deal Endings</h3>
          </div>
          <div className="overflow-x-auto -mx-4 sm:mx-0">
            <div className="inline-block min-w-full align-middle">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700">
                    <TableHead className="text-slate-300 text-xs sm:text-sm">#</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm">Customer</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm hidden sm:table-cell">Disb. Date</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm">End Date</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-center">Days Left</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">Amount</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden lg:table-cell">Profit</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-center hidden md:table-cell">Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {upcomingDeals.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={8} className="text-center py-8 text-slate-400 text-sm">
                        No ongoing deals
                      </TableCell>
                    </TableRow>
                  ) : (
                    upcomingDeals.map((deal) => (
                      <TableRow key={deal.transaction_number} className="border-slate-700/50">
                        <TableCell className="text-white font-medium text-xs sm:text-sm">{deal.transaction_number}</TableCell>
                        <TableCell className="text-white text-xs sm:text-sm">{deal.customer}</TableCell>
                        <TableCell className="text-slate-300 text-xs sm:text-sm hidden sm:table-cell">{formatDateMMYY(deal.disbursementDate)}</TableCell>
                        <TableCell className="text-slate-300 text-xs sm:text-sm">{formatDateMMYY(deal.endDate)}</TableCell>
                        <TableCell className="text-center">
                          <Badge className={deal.daysRemaining < 7 ? "bg-red-500/20 text-red-400 border-red-500/50 text-xs" : "bg-blue-500/20 text-blue-400 border-blue-500/50 text-xs"}>
                            {deal.daysRemaining}d
                          </Badge>
                        </TableCell>
                        <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatNumber(deal.amount)}</TableCell>
                        <TableCell className="text-right text-emerald-400 font-mono font-semibold text-xs sm:text-sm hidden lg:table-cell">{formatNumber(deal.expectedProfit)}</TableCell>
                        <TableCell className="text-center hidden md:table-cell">
                          <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50 text-xs">Ongoing</Badge>
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
        </Card>

        {/* Customer Yield Ranking & Analysis */}
        <Card className="bg-slate-800/30 border-slate-700 p-4 sm:p-6 mb-6 sm:mb-8">
          <h3 className="text-white text-base sm:text-lg font-semibold mb-4">Customer Yield Ranking & Analysis</h3>
          <div className="overflow-x-auto -mx-4 sm:mx-0">
            <div className="inline-block min-w-full align-middle">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700">
                    <TableHead className="text-slate-300 text-xs sm:text-sm">Customer</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-center">TTL Txns</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-center">Ended</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-center">Open</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">Disbursed</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Total Gross</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">Net Profit</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">Profit %</TableHead>
                    <TableHead 
                      className="text-slate-300 text-xs sm:text-sm text-right cursor-pointer hover:text-white transition-colors"
                      onClick={() => handleCustomerSort('perAnnumProfitPercent')}
                    >
                      <div className="flex items-center justify-end gap-1">
                        Per Annum %
                        <ArrowUpDown className="w-3 h-3" />
                      </div>
                    </TableHead>
                    <TableHead 
                      className="text-slate-300 text-xs sm:text-sm text-right cursor-pointer hover:text-white transition-colors hidden lg:table-cell"
                      onClick={() => handleCustomerSort('riskAdjustedYield')}
                    >
                      <div className="flex items-center justify-end gap-1">
                        Risk-Adj Yield
                        <ArrowUpDown className="w-3 h-3" />
                      </div>
                    </TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Share of Profit</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Share of Disb.</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Avg Ticket Size</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {sortedCustomerAnalysis.map((customer) => (
                    <TableRow key={customer.name} className="border-slate-700/50">
                      <TableCell className="text-white font-medium text-xs sm:text-sm">{customer.name}</TableCell>
                      <TableCell className="text-center text-slate-300 text-xs sm:text-sm">{customer.total}</TableCell>
                      <TableCell className="text-center text-green-400 text-xs sm:text-sm">{customer.ended}</TableCell>
                      <TableCell className="text-center text-blue-400 text-xs sm:text-sm">{customer.ongoing}</TableCell>
                      <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatNumber(customer.totalDisbursed)}</TableCell>
                      <TableCell className="text-right text-indigo-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatNumber(customer.totalGross)}</TableCell>
                      <TableCell className="text-right text-emerald-400 font-mono font-semibold text-xs sm:text-sm">{formatNumber(customer.netProfit)}</TableCell>
                      <TableCell className="text-right text-amber-400 text-xs sm:text-sm">{formatPercent(customer.profitPercent)}</TableCell>
                      <TableCell className="text-right text-orange-400 font-semibold text-xs sm:text-sm">{formatPercent(customer.perAnnumProfitPercent)}</TableCell>
                      <TableCell className="text-right text-cyan-400 font-semibold text-xs sm:text-sm hidden lg:table-cell">{formatPercent(customer.riskAdjustedYield)}</TableCell>
                      <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden sm:table-cell">{formatPercent(customer.shareOfProfit)}</TableCell>
                      <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden sm:table-cell">{formatPercent(customer.shareOfDisbursements)}</TableCell>
                      <TableCell className="text-right text-cyan-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatNumber(customer.avgTicketSize)}</TableCell>
                    </TableRow>
                  ))}
                  <TableRow className="border-slate-700 bg-slate-800/50 font-bold">
                    <TableCell className="text-white text-xs sm:text-sm">Total</TableCell>
                    <TableCell className="text-center text-slate-300 text-xs sm:text-sm">{sortedCustomerAnalysis.reduce((s, c) => s + c.total, 0)}</TableCell>
                    <TableCell className="text-center text-green-400 text-xs sm:text-sm">{sortedCustomerAnalysis.reduce((s, c) => s + c.ended, 0)}</TableCell>
                    <TableCell className="text-center text-blue-400 text-xs sm:text-sm">{sortedCustomerAnalysis.reduce((s, c) => s + c.ongoing, 0)}</TableCell>
                    <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatNumber(sortedCustomerAnalysis.reduce((s, c) => s + c.totalDisbursed, 0))}</TableCell>
                    <TableCell className="text-right text-indigo-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatNumber(sortedCustomerAnalysis.reduce((s, c) => s + c.totalGross, 0))}</TableCell>
                    <TableCell className="text-right text-emerald-400 font-mono font-semibold text-xs sm:text-sm">{formatNumber(sortedCustomerAnalysis.reduce((s, c) => s + c.netProfit, 0))}</TableCell>
                    <TableCell className="text-right text-amber-400 text-xs sm:text-sm">{formatPercent(
                      sortedCustomerAnalysis.reduce((s, c) => s + c.totalDisbursed, 0) > 0
                      ? (sortedCustomerAnalysis.reduce((s, c) => s + c.netProfit, 0) / sortedCustomerAnalysis.reduce((s, c) => s + c.totalDisbursed, 0)) * 100
                      : 0
                    )}</TableCell>
                    <TableCell className="text-right text-orange-400 font-semibold text-xs sm:text-sm">{formatPercent(metrics.perAnnumProfit)}</TableCell>
                    <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden lg:table-cell">-</TableCell>
                    <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden sm:table-cell">-</TableCell>
                    <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden sm:table-cell">-</TableCell>
                    <TableCell className="text-right text-cyan-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatNumber(
                      sortedCustomerAnalysis.reduce((s, c) => s + c.total, 0) > 0
                      ? sortedCustomerAnalysis.reduce((s, c) => s + c.totalDisbursed, 0) / sortedCustomerAnalysis.reduce((s, c) => s + c.total, 0)
                      : 0
                    )}</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>
        </Card>

        {/* Investor Summary - Actual */}
        <Card className="bg-slate-800/30 border-slate-700 p-4 sm:p-6 mb-6 sm:mb-8">
          <h3 className="text-white text-base sm:text-lg font-semibold mb-4">Investor Summary - Profit Actual (Realized Only)</h3>
          <div className="overflow-x-auto -mx-4 sm:mx-0">
            <div className="inline-block min-w-full align-middle">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700">
                    <TableHead className="text-slate-300 text-xs sm:text-sm">Name / Entity</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">Profit Actual</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Principal Actual</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Profit %</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">TTL Amount</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden md:table-cell">Share %</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {investorSummary.map((inv) => (
                    <TableRow key={inv.name} className="border-slate-700/50">
                      <TableCell className="text-white font-medium text-xs sm:text-sm">{inv.name}</TableCell>
                      <TableCell className="text-right text-emerald-400 font-mono text-xs sm:text-sm">{formatCurrency(inv.profitActual)}</TableCell>
                      <TableCell className="text-right text-blue-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatCurrency(inv.principal)}</TableCell>
                      <TableCell className="text-right text-amber-400 text-xs sm:text-sm hidden sm:table-cell">{formatPercent(inv.profitPercentActual)}</TableCell>
                      <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatCurrency(inv.totalActual)}</TableCell>
                      <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden md:table-cell">{totalActualShare > 0 ? formatPercent((inv.totalActual / totalActualShare) * 100) : formatPercent(0)}</TableCell>
                    </TableRow>
                  ))}
                  <TableRow className="border-slate-700 bg-slate-800/50 font-bold">
                    <TableCell className="text-white text-xs sm:text-sm">Total</TableCell>
                    <TableCell className="text-right text-emerald-400 font-mono text-xs sm:text-sm">{formatCurrency(investorSummary.reduce((s, i) => s + i.profitActual, 0))}</TableCell>
                    <TableCell className="text-right text-blue-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatCurrency(investorSummary.reduce((s, i) => s + i.principal, 0))}</TableCell>
                    <TableCell className="text-right text-amber-400 text-xs sm:text-sm hidden sm:table-cell">{formatPercent(
                      investorSummary.reduce((s, i) => s + i.principal, 0) > 0 
                      ? (investorSummary.reduce((s, i) => s + i.profitActual, 0) / investorSummary.reduce((s, i) => s + i.principal, 0)) * 100 
                      : 0
                    )}</TableCell>
                    <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatCurrency(totalActualShare)}</TableCell>
                    <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden md:table-cell">100%</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>
        </Card>

        {/* Investor Summary - With Pending */}
        <Card className="bg-slate-800/30 border-slate-700 p-4 sm:p-6 mb-6 sm:mb-8">
          <h3 className="text-white text-base sm:text-lg font-semibold mb-4">Investor Summary - Profit + With Pending Deals</h3>
          <div className="overflow-x-auto -mx-4 sm:mx-0">
            <div className="inline-block min-w-full align-middle">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700">
                    <TableHead className="text-slate-300 text-xs sm:text-sm">Name / Entity</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">Profit + Pending</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Principal Actual</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden sm:table-cell">Profit %</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right">TTL Amount</TableHead>
                    <TableHead className="text-slate-300 text-xs sm:text-sm text-right hidden md:table-cell">Share %</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {investorSummary.map((inv) => (
                    <TableRow key={inv.name} className="border-slate-700/50">
                      <TableCell className="text-white font-medium text-xs sm:text-sm">{inv.name}</TableCell>
                      <TableCell className="text-right text-emerald-400 font-mono text-xs sm:text-sm">{formatCurrency(inv.profitWithPending)}</TableCell>
                      <TableCell className="text-right text-blue-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatCurrency(inv.principal)}</TableCell>
                      <TableCell className="text-right text-amber-400 text-xs sm:text-sm hidden sm:table-cell">{formatPercent(inv.profitPercentWithPending)}</TableCell>
                      <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatCurrency(inv.totalWithPending)}</TableCell>
                      <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden md:table-cell">{totalWithPendingShare > 0 ? formatPercent((inv.totalWithPending / totalWithPendingShare) * 100) : formatPercent(0)}</TableCell>
                    </TableRow>
                  ))}
                  <TableRow className="border-slate-700 bg-slate-800/50 font-bold">
                    <TableCell className="text-white text-xs sm:text-sm">Total</TableCell>
                    <TableCell className="text-right text-emerald-400 font-mono text-xs sm:text-sm">{formatCurrency(investorSummary.reduce((s, i) => s + i.profitWithPending, 0))}</TableCell>
                    <TableCell className="text-right text-blue-400 font-mono text-xs sm:text-sm hidden sm:table-cell">{formatCurrency(investorSummary.reduce((s, i) => s + i.principal, 0))}</TableCell>
                    <TableCell className="text-right text-amber-400 text-xs sm:text-sm hidden sm:table-cell">{formatPercent(
                      investorSummary.reduce((s, i) => s + i.principal, 0) > 0
                      ? (investorSummary.reduce((s, i) => s + i.profitWithPending, 0) / investorSummary.reduce((s, i) => s + i.principal, 0)) * 100
                      : 0
                    )}</TableCell>
                    <TableCell className="text-right text-purple-400 font-mono text-xs sm:text-sm">{formatCurrency(totalWithPendingShare)}</TableCell>
                    <TableCell className="text-right text-slate-300 text-xs sm:text-sm hidden md:table-cell">100%</TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
}