import React, { useMemo, useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Zap, Droplets, RefreshCw, TrendingUp, AlertTriangle, Shield, Users, BarChart3, Activity, Target, Bell, TrendingDown, Minus } from "lucide-react";
import { format, addDays, differenceInDays, startOfWeek, endOfWeek, startOfMonth, endOfMonth, eachMonthOfInterval, subMonths, subDays } from "date-fns";
import PortfolioHealthScore from "../components/analytics/PortfolioHealthScore";
import AlertsDashboard from "../components/analytics/AlertsDashboard";

export default function AnalyticsPage() {
  const [exposureView, setExposureView] = useState('ongoing');

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

  const metrics = useMemo(() => {
    const totalPrincipal = investments.reduce((sum, inv) => sum + inv.amount, 0);
    
    const realizedProfit = allocations
      .filter(a => a.deal_status === "Ended")
      .reduce((sum, a) => sum + a.individual_profit, 0);
    
    const totalFund = totalPrincipal + realizedProfit;
    
    const lockedAmount = transactions
      .filter(t => t.status === "Ongoing")
      .reduce((sum, t) => sum + t.net_amount, 0);
    
    const pendingExpenses = expenses
      .filter(e => e.status === "Pending")
      .reduce((sum, e) => sum + e.amount, 0);
    
    const availableBalance = totalFund - lockedAmount - pendingExpenses;
    
    const ongoingTransactions = transactions.filter(t => t.status === "Ongoing");

    let weightedPortfolioAPY = 0;
    if (ongoingTransactions.length > 0) {
      const totalOngoingExposure = ongoingTransactions.reduce((sum, t) => sum + t.net_amount, 0);
      
      weightedPortfolioAPY = ongoingTransactions.reduce((sum, t) => {
        const weight = t.net_amount / totalOngoingExposure;
        const profitPercent = t.profit_margin - ((t.disbursement_charges || 0) / t.net_amount * 100);
        const annualizedProfit = t.sales_cycle > 0 ? profitPercent * (360 / t.sales_cycle) : 0;
        return sum + (annualizedProfit * weight);
      }, 0);
    }

    return {
      totalFund,
      lockedAmount,
      availableBalance,
      weightedPortfolioAPY,
      idlePercent: totalFund > 0 ? (availableBalance / totalFund) * 100 : 0
    };
  }, [investments, transactions, allocations, expenses]);

  const deploymentCurve = useMemo(() => {
    const today = new Date();
    const weeks = [];
    
    for (let i = 0; i < 12; i++) {
      const weekStart = startOfWeek(addDays(today, i * 7), { weekStartsOn: 1 });
      const weekEnd = endOfWeek(weekStart, { weekStartsOn: 1 });
      
      let lockedCapital = 0;
      
      transactions.filter(t => t.status === "Ongoing").forEach(t => {
        const disbursementDate = new Date(t.date_of_transaction);
        const dealEndDate = addDays(disbursementDate, t.sales_cycle);
        
        if (disbursementDate <= weekEnd && dealEndDate >= weekStart) {
          lockedCapital += t.net_amount;
        }
      });
      
      weeks.push({
        weekNumber: i + 1,
        weekStart,
        weekEnd,
        lockedCapital,
        label: format(weekStart, 'MMM dd')
      });
    }
    
    return weeks;
  }, [transactions]);

  const fundingGapForecast = useMemo(() => {
    const today = new Date();
    
    const upcomingReturns = transactions
      .filter(t => t.status === "Ongoing")
      .map(t => {
        const endDate = addDays(new Date(t.date_of_transaction), t.sales_cycle);
        const daysUntilReturn = differenceInDays(endDate, today);
        
        // Calculate net profit for this transaction
        const transactionNetProfit = (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0);
        
        // Total returning = principal + net profit
        const totalReturning = t.net_amount + transactionNetProfit;
        
        return {
          date: endDate,
          amount: totalReturning,
          principal: t.net_amount,
          profit: transactionNetProfit,
          daysUntilReturn,
          customer: t.customer
        };
      })
      .filter(r => r.daysUntilReturn >= 0 && r.daysUntilReturn <= 30)
      .sort((a, b) => a.daysUntilReturn - b.daysUntilReturn);
    
    let cumulativeReturns = 0;
    const projectedBalance = upcomingReturns.map(ret => {
      cumulativeReturns += ret.amount;
      return {
        ...ret,
        projectedBalance: metrics.availableBalance + cumulativeReturns
      };
    });
    
    return {
      currentAvailable: metrics.availableBalance,
      totalReturning: upcomingReturns.reduce((sum, r) => sum + r.amount, 0),
      upcomingReturns: projectedBalance
    };
  }, [transactions, metrics.availableBalance]);

  const idlePercentByMonth = useMemo(() => {
    const today = new Date();
    const months = eachMonthOfInterval({
      start: subMonths(today, 11),
      end: today
    });
    
    return months.map(month => {
      const monthStart = startOfMonth(month);
      const monthEnd = endOfMonth(month);
      
      const principalAtMonth = investments
        .filter(inv => new Date(inv.date) <= monthEnd)
        .reduce((sum, inv) => sum + inv.amount, 0);
      
      const realizedProfitAtMonth = allocations
        .filter(a => a.deal_status === "Ended" && new Date(a.deal_end_date) <= monthEnd)
        .reduce((sum, a) => sum + a.individual_profit, 0);
      
      const totalFundAtMonth = principalAtMonth + realizedProfitAtMonth;
      
      const daysInMonth = differenceInDays(monthEnd, monthStart);
      let totalLockedDays = 0;
      
      transactions.filter(t => t.status !== "Not Disbursed").forEach(t => {
        const disbursementDate = new Date(t.date_of_transaction);
        const dealEndDate = addDays(disbursementDate, t.sales_cycle);
        
        const overlapStart = disbursementDate > monthStart ? disbursementDate : monthStart;
        const overlapEnd = dealEndDate < monthEnd ? dealEndDate : monthEnd;
        
        if (overlapStart <= overlapEnd && disbursementDate <= monthEnd && dealEndDate >= monthStart) {
          const daysLocked = differenceInDays(overlapEnd, overlapStart) + 1;
          totalLockedDays += (t.net_amount * daysLocked);
        }
      });
      
      const avgLockedCapital = daysInMonth > 0 ? totalLockedDays / daysInMonth : 0;
      const avgAvailable = totalFundAtMonth - avgLockedCapital;
      const idlePercent = totalFundAtMonth > 0 ? (avgAvailable / totalFundAtMonth) * 100 : 0;
      
      return {
        month: format(month, 'MMM yy'),
        idlePercent: Math.max(0, idlePercent),
        totalFund: totalFundAtMonth,
        avgLocked: avgLockedCapital
      };
    });
  }, [investments, allocations, transactions]);

  const dealRecyclingSpeed = useMemo(() => {
    const avgDeployedCapital = metrics.totalFund > 0 ? metrics.lockedAmount : 0;
    
    const endedTransactions = transactions.filter(t => t.status === "Ended");
    
    if (endedTransactions.length === 0) {
      return {
        cyclesPerMonth: 0,
        monthlyVolume: 0,
        velocityRatio: 0,
        avgDeployed: avgDeployedCapital
      };
    }
    
    const allDates = endedTransactions.map(t => new Date(t.date_of_transaction));
    
    if (allDates.length === 0) {
      return {
        cyclesPerMonth: 0,
        monthlyVolume: 0,
        velocityRatio: 0,
        avgDeployed: avgDeployedCapital
      };
    }
    
    const oldestDate = new Date(Math.min(...allDates));
    const totalDays = differenceInDays(new Date(), oldestDate);
    const totalMonths = totalDays / 30;
    
    if (totalMonths === 0) {
      return {
        cyclesPerMonth: 0,
        monthlyVolume: 0,
        velocityRatio: 0,
        avgDeployed: avgDeployedCapital
      };
    }
    
    const totalVolume = endedTransactions.reduce((sum, t) => sum + t.net_amount, 0);
    const monthlyVolume = totalVolume / totalMonths;
    
    const cyclesPerMonth = avgDeployedCapital > 0 ? monthlyVolume / avgDeployedCapital : 0;
    
    return {
      cyclesPerMonth: cyclesPerMonth,
      monthlyVolume: monthlyVolume,
      velocityRatio: cyclesPerMonth,
      avgDeployed: avgDeployedCapital
    };
  }, [transactions, metrics.totalFund, metrics.lockedAmount]);

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
      return b.perAnnumProfitPercent - a.perAnnumProfitPercent;
    });
  }, [customerAnalysis]);

  const customerExposure = useMemo(() => {
    const ongoingTransactions = transactions.filter(t => t.status === "Ongoing");
    const exposureByCustomer = {};
    
    ongoingTransactions.forEach(t => {
      if (!exposureByCustomer[t.customer]) {
        exposureByCustomer[t.customer] = {
          totalExposure: 0,
          dealCount: 0
        };
      }
      exposureByCustomer[t.customer].totalExposure += t.net_amount;
      exposureByCustomer[t.customer].dealCount += 1;
    });
    
    const totalOngoingExposure = Object.values(exposureByCustomer).reduce((sum, c) => sum + c.totalExposure, 0);
    const denominator = exposureView === 'ongoing' ? totalOngoingExposure : metrics.totalFund;
    
    return Object.entries(exposureByCustomer)
      .map(([customer, data]) => ({
        customer,
        exposure: data.totalExposure,
        dealCount: data.dealCount,
        exposurePercent: denominator > 0 ? (data.totalExposure / denominator) * 100 : 0
      }))
      .sort((a, b) => b.exposure - a.exposure);
  }, [transactions, exposureView, metrics.totalFund]);

  const counterpartyRisk = useMemo(() => {
    const sortedByProfit = [...sortedCustomerAnalysis].sort((a, b) => b.netProfit - a.netProfit);
    const totalProfit = sortedByProfit.reduce((sum, c) => sum + c.netProfit, 0);
    
    const top3Customers = sortedByProfit.slice(0, 3);
    const top3ProfitPercent = totalProfit > 0 
      ? (top3Customers.reduce((sum, c) => sum + c.netProfit, 0) / totalProfit) * 100 
      : 0;
    
    const totalExposure = transactions.filter(t => t.status === "Ongoing").reduce((sum, t) => sum + t.net_amount, 0);
    const exposureShares = customerExposure.map(c => 
      totalExposure > 0 ? (c.exposure / totalExposure) * 100 : 0
    );
    const hhi = exposureShares.reduce((sum, share) => sum + (share * share), 0);
    
    const maxSingleExposure = customerExposure.length > 0 
      ? Math.max(...customerExposure.map(c => c.exposurePercent))
      : 0;
    
    return {
      top3Customers,
      top3ProfitPercent,
      hhi,
      maxSingleExposure,
      totalProfit
    };
  }, [sortedCustomerAnalysis, customerExposure, transactions]);

  const customerQualityScores = useMemo(() => {
    return sortedCustomerAnalysis
      .filter(customer => customer.total >= 3)
      .map(customer => {
        const customerExposureData = customerExposure.find(c => c.customer === customer.name);
        const exposurePercent = customerExposureData ? customerExposureData.exposurePercent : 0;
        
        const profitScore = Math.min(customer.perAnnumProfitPercent / 20 * 100, 100);
        
        let tenorScore;
        if (customer.avgCustomerTenor < 60) tenorScore = 100;
        else if (customer.avgCustomerTenor <= 90) tenorScore = 80;
        else if (customer.avgCustomerTenor <= 120) tenorScore = 60;
        else tenorScore = Math.max(0, 60 - (customer.avgCustomerTenor - 120) / 2);
        
        const concentrationPenalty = exposurePercent > 25 
          ? Math.max(0, 100 - (exposurePercent - 25) * 3)
          : 100;
        
        const qualityScore = (profitScore * 0.4 + tenorScore * 0.3 + concentrationPenalty * 0.3);
        
        return {
          customer: customer.name,
          qualityScore,
          profitScore,
          tenorScore,
          concentrationPenalty,
          perAnnumProfit: customer.perAnnumProfitPercent,
          avgTenor: customer.avgCustomerTenor,
          exposurePercent,
          dealCount: customer.total
        };
      }).sort((a, b) => b.qualityScore - a.qualityScore);
  }, [sortedCustomerAnalysis, customerExposure]);

  const investorIntelligence = useMemo(() => {
    const today = new Date();
    const ninetyDaysAgo = subDays(today, 90);
    
    const investorData = {};
    
    investments.forEach(inv => {
      if (!investorData[inv.name]) {
        investorData[inv.name] = {
          totalPrincipal: 0,
          realizedProfit: 0,
          pendingProfit: 0,
          totalCapital: 0,
          recentProfits: 0,
          lastInvestmentDate: new Date(inv.date),
          investments: []
        };
      }
      investorData[inv.name].totalPrincipal += inv.amount;
      investorData[inv.name].investments.push(inv);
      const invDate = new Date(inv.date);
      if (invDate > investorData[inv.name].lastInvestmentDate) {
        investorData[inv.name].lastInvestmentDate = invDate;
      }
    });
    
    allocations.forEach(alloc => {
      if (!investorData[alloc.investor_name]) {
        investorData[alloc.investor_name] = {
          totalPrincipal: 0,
          realizedProfit: 0,
          pendingProfit: 0,
          totalCapital: 0,
          recentProfits: 0,
          lastInvestmentDate: null,
          investments: []
        };
      }
      
      if (alloc.deal_status === "Ended") {
        investorData[alloc.investor_name].realizedProfit += alloc.individual_profit;
        
        const dealEndDate = new Date(alloc.deal_end_date);
        if (dealEndDate >= ninetyDaysAgo) {
          investorData[alloc.investor_name].recentProfits += alloc.individual_profit;
        }
      } else if (alloc.deal_status === "Ongoing") {
        investorData[alloc.investor_name].pendingProfit += alloc.individual_profit;
      }
    });
    
    const totalRealizedProfit = Object.values(investorData).reduce((sum, inv) => sum + inv.realizedProfit, 0);
    
    return Object.entries(investorData).map(([name, data]) => {
      data.totalCapital = data.totalPrincipal + data.realizedProfit;
      
      const weightedYield = data.totalPrincipal > 0 
        ? (data.realizedProfit / data.totalPrincipal) * 100 
        : 0;
      
      const profitToPrincipalPercent = data.totalPrincipal > 0 
        ? (data.realizedProfit / data.totalPrincipal) * 100 
        : 0;
      
      const hasRecentProfits = data.recentProfits > 0;
      const redemptionRisk = !hasRecentProfits && profitToPrincipalPercent < 3;
      
      const daysSinceLastInvestment = data.lastInvestmentDate 
        ? differenceInDays(new Date(), data.lastInvestmentDate) 
        : 999;
      
      const ongoingDealsForInvestor = allocations.filter(
        a => a.investor_name === name && a.deal_status === "Ongoing"
      );
      
      const deployedCapital = ongoingDealsForInvestor.reduce((sum, a) => sum + a.invested_capital, 0);
      const deploymentRate = data.totalCapital > 0 ? (deployedCapital / data.totalCapital) * 100 : 0;
      
      const absorptionScore = daysSinceLastInvestment < 90 
        ? Math.min(100, deploymentRate * 1.2) 
        : deploymentRate;
      
      return {
        name,
        totalPrincipal: data.totalPrincipal,
        realizedProfit: data.realizedProfit,
        pendingProfit: data.pendingProfit,
        totalCapital: data.totalCapital,
        weightedYield,
        recentProfits: data.recentProfits,
        redemptionRisk,
        daysSinceLastInvestment,
        deployedCapital,
        deploymentRate,
        absorptionScore
      };
    }).sort((a, b) => b.totalCapital - a.totalCapital);
  }, [investments, allocations]);

  const dealPerformanceDiagnostics = useMemo(() => {
    const dealData = transactions.map(t => {
      const profitPercent = t.profit_margin - ((t.disbursement_charges || 0) / t.net_amount * 100);
      return {
        ticketSize: t.net_amount,
        profitPercent,
        tenor: t.sales_cycle,
        customer: t.customer,
        status: t.status
      };
    });

    const calculateCorrelation = (x, y) => {
      const n = x.length;
      const sumX = x.reduce((a, b) => a + b, 0);
      const sumY = y.reduce((a, b) => a + b, 0);
      const sumXY = x.reduce((sum, xi, i) => sum + xi * y[i], 0);
      const sumX2 = x.reduce((sum, xi) => sum + xi * xi, 0);
      const sumY2 = y.reduce((sum, yi) => sum + yi * yi, 0);
      
      const numerator = n * sumXY - sumX * sumY;
      const denominator = Math.sqrt((n * sumX2 - sumX * sumX) * (n * sumY2 - sumY * sumY));
      
      return denominator === 0 ? 0 : numerator / denominator;
    };

    const ticketSizes = dealData.map(d => d.ticketSize);
    const profitPercents = dealData.map(d => d.profitPercent);
    const tenors = dealData.map(d => d.tenor);
    
    const ticketProfitCorrelation = calculateCorrelation(ticketSizes, profitPercents);
    const tenorProfitCorrelation = calculateCorrelation(tenors, profitPercents);

    const tenorBuckets = [
      { min: 0, max: 30, label: '0-30d' },
      { min: 31, max: 60, label: '31-60d' },
      { min: 61, max: 90, label: '61-90d' },
      { min: 91, max: 120, label: '91-120d' },
      { min: 121, max: 999, label: '120d+' }
    ];

    const tenorProfitCurve = tenorBuckets.map(bucket => {
      const dealsInBucket = dealData.filter(d => d.tenor >= bucket.min && d.tenor <= bucket.max);
      const avgProfit = dealsInBucket.length > 0 
        ? dealsInBucket.reduce((sum, d) => sum + d.profitPercent, 0) / dealsInBucket.length 
        : 0;
      return {
        label: bucket.label,
        avgProfit,
        dealCount: dealsInBucket.length
      };
    });

    const customerConsistency = {};
    transactions.forEach(t => {
      const profitPercent = t.profit_margin - ((t.disbursement_charges || 0) / t.net_amount * 100);
      if (!customerConsistency[t.customer]) {
        customerConsistency[t.customer] = {
          profits: [],
          dealCount: 0
        };
      }
      customerConsistency[t.customer].profits.push(profitPercent);
      customerConsistency[t.customer].dealCount += 1;
    });

    const customerVariance = Object.entries(customerConsistency).map(([customer, data]) => {
      if (data.profits.length < 2) {
        return { customer, variance: 0, stdDev: 0, avgProfit: data.profits[0] || 0, dealCount: data.dealCount };
      }
      
      const mean = data.profits.reduce((sum, p) => sum + p, 0) / data.profits.length;
      const variance = data.profits.reduce((sum, p) => sum + Math.pow(p - mean, 2), 0) / data.profits.length;
      const stdDev = Math.sqrt(variance);
      
      return {
        customer,
        variance,
        stdDev,
        avgProfit: mean,
        dealCount: data.dealCount
      };
    }).sort((a, b) => b.variance - a.variance);

    const allProfits = transactions.map(t => 
      (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0)
    ).sort((a, b) => a - b);
    
    const meanProfit = allProfits.reduce((sum, p) => sum + p, 0) / allProfits.length;
    const medianProfit = allProfits.length % 2 === 0
      ? (allProfits[allProfits.length / 2 - 1] + allProfits[allProfits.length / 2]) / 2
      : allProfits[Math.floor(allProfits.length / 2)];

    const totalRealizedProfit = allocations
      .filter(a => a.deal_status === "Ended")
      .reduce((sum, a) => sum + a.individual_profit, 0);
    
    const totalExpenses = expenses.reduce((sum, e) => sum + e.amount, 0);
    const pendingExpenses = expenses.filter(e => e.status === "Pending").reduce((sum, e) => sum + e.amount, 0);
    
    const threeMonthsAgo = subMonths(new Date(), 3);
    const recentExpenses = expenses.filter(e => new Date(e.date) >= threeMonthsAgo);
    const monthlyBurn = recentExpenses.length > 0 
      ? recentExpenses.reduce((sum, e) => sum + e.amount, 0) / 3 
      : 0;
    
    const profitAfterOPEX = totalRealizedProfit - totalExpenses;
    const projectedAnnualOPEX = monthlyBurn * 12;
    const opexToProfitRatio = totalRealizedProfit > 0 ? (totalExpenses / totalRealizedProfit) * 100 : 0;

    return {
      ticketProfitCorrelation,
      tenorProfitCorrelation,
      tenorProfitCurve,
      customerVariance,
      meanProfit,
      medianProfit,
      totalRealizedProfit,
      totalExpenses,
      pendingExpenses,
      monthlyBurn,
      profitAfterOPEX,
      projectedAnnualOPEX,
      opexToProfitRatio
    };
  }, [transactions, allocations, expenses]);

  const predictiveAnalytics = useMemo(() => {
    const today = new Date();
    
    const next30DaysDeals = transactions
      .filter(t => t.status === "Ongoing")
      .map(t => {
        const endDate = addDays(new Date(t.date_of_transaction), t.sales_cycle);
        const daysUntilEnd = differenceInDays(endDate, today);
        const profit = (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0);
        
        return {
          endDate,
          daysUntilEnd,
          profit,
          amount: t.net_amount,
          customer: t.customer,
          transactionNumber: t.transaction_number
        };
      })
      .filter(d => d.daysUntilEnd >= 0 && d.daysUntilEnd <= 30)
      .sort((a, b) => a.daysUntilEnd - b.daysUntilEnd);
    
    let cumulativeProfit = 0;
    const profitForwardCurve = next30DaysDeals.map(deal => {
      cumulativeProfit += deal.profit;
      return {
        ...deal,
        cumulativeProfit
      };
    });
    
    const returningCapitalNext30 = next30DaysDeals.reduce((sum, d) => sum + d.amount + d.profit, 0);
    const maxDeploymentCapacity = metrics.availableBalance + returningCapitalNext30;
    
    const next90DaysWeeks = [];
    for (let i = 0; i < 13; i++) {
      const weekStart = addDays(today, i * 7);
      const weekEnd = addDays(weekStart, 6);
      
      let lockedCapital = 0;
      transactions.filter(t => t.status === "Ongoing").forEach(t => {
        const disbursementDate = new Date(t.date_of_transaction);
        const dealEndDate = addDays(disbursementDate, t.sales_cycle);
        
        if (disbursementDate <= weekEnd && dealEndDate >= weekStart) {
          lockedCapital += t.net_amount;
        }
      });
      
      const idleCapital = metrics.totalFund - lockedCapital;
      const idlePercent = metrics.totalFund > 0 ? (idleCapital / metrics.totalFund) * 100 : 0;
      
      next90DaysWeeks.push({
        weekStart,
        weekEnd,
        lockedCapital,
        idleCapital,
        idlePercent,
        label: format(weekStart, 'MMM dd')
      });
    }
    
    const idleValley = next90DaysWeeks.reduce((max, week) => 
      week.idlePercent > max.idlePercent ? week : max
    , next90DaysWeeks[0]);
    
    const next10DealsEnding = transactions
      .filter(t => t.status === "Ongoing")
      .map(t => {
        const endDate = addDays(new Date(t.date_of_transaction), t.sales_cycle);
        const daysUntilEnd = differenceInDays(endDate, today);
        const profit = (t.net_amount * t.profit_margin / 100) - (t.disbursement_charges || 0);
        
        return {
          transactionNumber: t.transaction_number,
          customer: t.customer,
          amount: t.net_amount + profit,
          principal: t.net_amount,
          profit: profit,
          endDate,
          daysUntilEnd
        };
      })
      .filter(d => d.daysUntilEnd >= 0)
      .sort((a, b) => a.daysUntilEnd - b.daysUntilEnd)
      .slice(0, 10);
    
    const totalReturningCapital = next10DealsEnding.reduce((sum, d) => sum + d.amount, 0);
    
    const topCustomersWithMinDeals = customerQualityScores.slice(0, 3).map(c => c.customer);
    const reallocationSuggestion = {
      returningAmount: totalReturningCapital,
      suggestedCustomers: topCustomersWithMinDeals.length > 0 ? topCustomersWithMinDeals : ['No qualifying customers'],
      rationale: `Based on APY + quality score, within exposure limits`
    };
    
    return {
      profitForwardCurve,
      totalExpectedProfit30d: cumulativeProfit,
      maxDeploymentCapacity,
      next90DaysWeeks,
      idleValley,
      next10DealsEnding,
      reallocationSuggestion
    };
  }, [transactions, metrics, customerQualityScores]);

  const portfolioHealthScore = useMemo(() => {
    const idlePercent = metrics.idlePercent;
    let liquidityScore;
    if (idlePercent >= 5 && idlePercent <= 10) {
      liquidityScore = 100;
    } else if (idlePercent < 5) {
      liquidityScore = Math.max(0, 100 - (5 - idlePercent) * 10);
    } else {
      liquidityScore = Math.max(0, 100 - (idlePercent - 10) * 5);
    }
    
    const yieldScore = Math.min(100, (metrics.weightedPortfolioAPY / 10) * 100);
    
    const hhi = counterpartyRisk.hhi;
    const counterpartyScore = hhi < 1500 ? 100 : hhi < 2500 ? 70 : hhi < 5000 ? 40 : 20;
    
    const investorsAtRisk = investorIntelligence.filter(i => i.redemptionRisk).length;
    const totalInvestors = investorIntelligence.length;
    const riskPercent = totalInvestors > 0 ? (investorsAtRisk / totalInvestors) * 100 : 0;
    const investorScore = Math.max(0, 100 - riskPercent * 2);
    
    const velocityScore = Math.min(100, (dealRecyclingSpeed.cyclesPerMonth / 1.5) * 100);
    
    const overall = (
      liquidityScore * 0.25 +
      yieldScore * 0.30 +
      counterpartyScore * 0.20 +
      investorScore * 0.15 +
      velocityScore * 0.10
    );
    
    return {
      overall,
      liquidity: liquidityScore,
      yield: yieldScore,
      counterpartyRisk: counterpartyScore,
      investorStability: investorScore,
      dealVelocity: velocityScore,
      rawMetrics: {
        idlePercent,
        weightedAPY: metrics.weightedPortfolioAPY,
        hhi,
        investorsAtRisk,
        cyclesPerMonth: dealRecyclingSpeed.cyclesPerMonth
      }
    };
  }, [metrics, counterpartyRisk, investorIntelligence, dealRecyclingSpeed]);

  const systemAlerts = useMemo(() => {
    const alerts = [];
    
    if (metrics.idlePercent > 8) {
      alerts.push({
        severity: 'warning',
        title: 'High Idle Funds',
        message: `Idle capital at ${formatPercent(metrics.idlePercent)}. Target: 5-10%. Action: Deploy to top-quality customers or onboard new counterparties.`,
        value: formatPercent(metrics.idlePercent)
      });
    }
    
    if (metrics.idlePercent < 5) {
      alerts.push({
        severity: 'warning',
        title: 'Low Liquidity Cushion',
        message: `Idle capital at ${formatPercent(metrics.idlePercent)}, below 5% target. Consider maintaining higher reserves.`,
        value: formatPercent(metrics.idlePercent)
      });
    }
    
    const highExposureCustomers = customerExposure.filter(c => c.exposurePercent > 35);
    if (highExposureCustomers.length > 0) {
      alerts.push({
        severity: 'critical',
        title: 'High Customer Concentration',
        message: `${highExposureCustomers.length} customer(s) exceed 35% exposure. Action: Diversify portfolio by reducing allocation to: ${highExposureCustomers.map(c => c.customer).join(', ')}`,
        value: highExposureCustomers.map(c => `${c.customer} (${formatPercent(c.exposurePercent)})`).join(', ')
      });
    }
    
    if (counterpartyRisk.hhi > 2500) {
      alerts.push({
        severity: 'critical',
        title: 'High Portfolio Concentration (HHI)',
        message: `HHI ${formatNumber(counterpartyRisk.hhi)} indicates concentrated risk. Action: Diversify exposure across more counterparties.`,
        value: formatNumber(counterpartyRisk.hhi)
      });
    }
    
    const investorsAtRisk = investorIntelligence.filter(i => i.redemptionRisk);
    if (investorsAtRisk.length > 0) {
      alerts.push({
        severity: 'warning',
        title: 'Investors at Redemption Risk',
        message: `${investorsAtRisk.length} investor(s) with no recent profits or low returns: ${investorsAtRisk.map(i => i.name).join(', ')}. Action: Review allocation strategy for these investors.`,
        value: `${investorsAtRisk.length} investors`
      });
    }
    
    if (dealRecyclingSpeed.cyclesPerMonth < 0.5) {
      alerts.push({
        severity: 'warning',
        title: 'Low Deal Velocity',
        message: `Deal recycling at ${dealRecyclingSpeed.cyclesPerMonth.toFixed(2)}x/month, below 0.5x target. Action: Increase deal throughput or reduce tenors.`,
        value: `${dealRecyclingSpeed.cyclesPerMonth.toFixed(2)}x/month`
      });
    }
    
    if (dealPerformanceDiagnostics.opexToProfitRatio > 40) {
      alerts.push({
        severity: 'critical',
        title: 'High OPEX-to-Profit Ratio',
        message: `Operational expenses are ${formatPercent(dealPerformanceDiagnostics.opexToProfitRatio)} of profit. Action: Review and optimize cost structure.`,
        value: formatPercent(dealPerformanceDiagnostics.opexToProfitRatio)
      });
    }
    
    if (portfolioHealthScore.overall < 50) {
      alerts.push({
        severity: 'critical',
        title: 'Portfolio Health Below Threshold',
        message: `Overall health score ${portfolioHealthScore.overall.toFixed(0)}/100. Action: Immediate attention required across multiple dimensions.`,
        value: `${portfolioHealthScore.overall.toFixed(0)}/100`
      });
    }
    
    if (predictiveAnalytics.idleValley.idlePercent > 15) {
      alerts.push({
        severity: 'info',
        title: 'Upcoming Idle Capital Peak',
        message: `Expected idle spike of ${formatPercent(predictiveAnalytics.idleValley.idlePercent)} during week of ${format(predictiveAnalytics.idleValley.weekStart, 'MMM dd')}. Action: Plan deployment ahead of this period.`,
        value: format(predictiveAnalytics.idleValley.weekStart, 'MMM dd, yyyy')
      });
    }
    
    const sortedAlerts = alerts.sort((a, b) => {
      const severityOrder = { critical: 0, warning: 1, info: 2 };
      return severityOrder[a.severity] - severityOrder[b.severity];
    });
    
    return sortedAlerts.slice(0, 5);
  }, [metrics, customerExposure, counterpartyRisk, investorIntelligence, dealRecyclingSpeed, dealPerformanceDiagnostics, portfolioHealthScore, predictiveAnalytics]);

  const thirtyDaysAgo = useMemo(() => subDays(new Date(), 30), []);
  
  const metrics30DaysAgo = useMemo(() => {
    const principalAtDate = investments
      .filter(inv => new Date(inv.date) <= thirtyDaysAgo)
      .reduce((sum, inv) => sum + inv.amount, 0);
    
    const realizedProfitAtDate = allocations
      .filter(a => a.deal_status === "Ended" && new Date(a.deal_end_date) <= thirtyDaysAgo)
      .reduce((sum, a) => sum + a.individual_profit, 0);
    
    const totalFundAtDate = principalAtDate + realizedProfitAtDate;
    
    const ongoingAt30Days = transactions.filter(t => {
      const disbDate = new Date(t.date_of_transaction);
      const endDate = addDays(disbDate, t.sales_cycle);
      return t.status !== "Not Disbursed" && disbDate <= thirtyDaysAgo && endDate >= thirtyDaysAgo;
    });
    
    const lockedAt30Days = ongoingAt30Days.reduce((sum, t) => sum + t.net_amount, 0);
    
    const pendingExpensesAt30Days = expenses
      .filter(e => e.status === "Pending" && new Date(e.date) <= thirtyDaysAgo)
      .reduce((sum, e) => sum + e.amount, 0);
    
    const availableAt30Days = totalFundAtDate - lockedAt30Days - pendingExpensesAt30Days;
    const idlePercentAt30Days = totalFundAtDate > 0 ? (availableAt30Days / totalFundAtDate) * 100 : 0;
    
    let weightedAPYAt30Days = 0;
    if (ongoingAt30Days.length > 0) {
      const totalExposure = ongoingAt30Days.reduce((sum, t) => sum + t.net_amount, 0);
      weightedAPYAt30Days = ongoingAt30Days.reduce((sum, t) => {
        const weight = t.net_amount / totalExposure;
        const profitPercent = t.profit_margin - ((t.disbursement_charges || 0) / t.net_amount * 100);
        const annualized = t.sales_cycle > 0 ? profitPercent * (360 / t.sales_cycle) : 0;
        return sum + (annualized * weight);
      }, 0);
    }
    
    const exposureByCustomerAt30 = {};
    ongoingAt30Days.forEach(t => {
      if (!exposureByCustomerAt30[t.customer]) {
        exposureByCustomerAt30[t.customer] = 0;
      }
      exposureByCustomerAt30[t.customer] += t.net_amount;
    });
    
    const totalExposureAt30 = Object.values(exposureByCustomerAt30).reduce((sum, exp) => sum + exp, 0);
    const sharesAt30 = Object.values(exposureByCustomerAt30).map(exp => 
      totalExposureAt30 > 0 ? (exp / totalExposureAt30) * 100 : 0
    );
    const hhiAt30Days = sharesAt30.reduce((sum, share) => sum + (share * share), 0);
    
    const maxExposureAt30 = sharesAt30.length > 0 ? Math.max(...sharesAt30) : 0;
    
    const investorsAtRiskAt30 = investorIntelligence.filter(inv => {
      const profitsFrom30DaysAgoTo60 = allocations
        .filter(a => {
          const endDate = new Date(a.deal_end_date);
          const sixtyDaysAgo = subDays(thirtyDaysAgo, 30);
          return a.investor_name === inv.name && a.deal_status === "Ended" && endDate >= sixtyDaysAgo && endDate < thirtyDaysAgo;
        })
        .reduce((sum, a) => sum + a.individual_profit, 0);
      
      const principalAt30 = investments
        .filter(i => i.name === inv.name && new Date(i.date) <= thirtyDaysAgo)
        .reduce((sum, i) => sum + i.amount, 0);
      
      const realizedAt30 = allocations
        .filter(a => a.investor_name === inv.name && a.deal_status === "Ended" && new Date(a.deal_end_date) <= thirtyDaysAgo)
        .reduce((sum, a) => sum + a.individual_profit, 0);
      
      const profitRatioAt30 = principalAt30 > 0 ? (realizedAt30 / principalAt30) * 100 : 0;
      
      return profitsFrom30DaysAgoTo60 === 0 && profitRatioAt30 < 3;
    }).length;
    
    const investorStabilityPercentAt30 = investorIntelligence.length > 0 
      ? ((investorIntelligence.length - investorsAtRiskAt30) / investorIntelligence.length) * 100 
      : 100;
    
    const endedBefore30Days = transactions.filter(t => {
      const endDate = addDays(new Date(t.date_of_transaction), t.sales_cycle);
      return t.status === "Ended" && endDate <= thirtyDaysAgo;
    });
    
    let cyclesAt30 = 0;
    if (endedBefore30Days.length > 0) {
      const oldestDate = new Date(Math.min(...endedBefore30Days.map(t => new Date(t.date_of_transaction))));
      const daysTo30 = differenceInDays(thirtyDaysAgo, oldestDate);
      const monthsTo30 = daysTo30 / 30;
      
      if (monthsTo30 > 0) {
        const volumeTo30 = endedBefore30Days.reduce((sum, t) => sum + t.net_amount, 0);
        const monthlyVolAt30 = volumeTo30 / monthsTo30;
        cyclesAt30 = lockedAt30Days > 0 ? monthlyVolAt30 / lockedAt30Days : 0;
      }
    }
    
    const liquidityScoreAt30 = idlePercentAt30Days >= 5 && idlePercentAt30Days <= 10 ? 100 
      : idlePercentAt30Days < 5 ? Math.max(0, 100 - (5 - idlePercentAt30Days) * 10) 
      : Math.max(0, 100 - (idlePercentAt30Days - 10) * 5);
    
    const yieldScoreAt30 = Math.min(100, (weightedAPYAt30Days / 10) * 100);
    const counterpartyScoreAt30 = hhiAt30Days < 1500 ? 100 : hhiAt30Days < 2500 ? 70 : hhiAt30Days < 5000 ? 40 : 20;
    const investorScoreAt30 = Math.max(0, investorStabilityPercentAt30 * 2 - 100);
    const velocityScoreAt30 = Math.min(100, (cyclesAt30 / 1.5) * 100);
    
    const healthAt30 = (
      liquidityScoreAt30 * 0.25 +
      yieldScoreAt30 * 0.30 +
      counterpartyScoreAt30 * 0.20 +
      investorScoreAt30 * 0.15 +
      velocityScoreAt30 * 0.10
    );
    
    return {
      weightedAPY: weightedAPYAt30Days,
      idlePercent: idlePercentAt30Days,
      hhi: hhiAt30Days,
      maxExposure: maxExposureAt30,
      investorStabilityPercent: investorStabilityPercentAt30,
      healthScore: healthAt30
    };
  }, [investments, allocations, transactions, expenses, thirtyDaysAgo, investorIntelligence]);

  const isLoading = investmentsLoading || transactionsLoading || allocationsLoading || expensesLoading;

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  const getTrendIcon = (current, previous) => {
    if (Math.abs(current - previous) < 0.01) return <Minus className="w-4 h-4" />;
    return current > previous ? <TrendingUp className="w-4 h-4" /> : <TrendingDown className="w-4 h-4" />;
  };

  const getTrendColor = (current, previous, higherIsBetter = true) => {
    if (Math.abs(current - previous) < 0.01) return "text-slate-400";
    const isUp = current > previous;
    if (higherIsBetter) {
      return isUp ? "text-green-400" : "text-red-400";
    } else {
      return isUp ? "text-red-400" : "text-green-400";
    }
  };

  const investorStabilityPercent = investorIntelligence.length > 0 
    ? ((investorIntelligence.length - investorIntelligence.filter(i => i.redemptionRisk).length) / investorIntelligence.length) * 100 
    : 100;

  return (
    <div className="p-3 sm:p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Top KPI Bar - Always Visible */}
        <div className="mb-6 sm:mb-8 sticky top-0 z-40 bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 pb-4">
          <div className="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
            {/* Weighted Portfolio APY */}
            <Card className="bg-gradient-to-br from-indigo-900/80 to-indigo-800/80 border-indigo-600 p-3 sm:p-4 backdrop-blur-sm">
              <div className="flex items-center justify-between mb-1">
                <p className="text-indigo-200 text-[10px] sm:text-xs font-semibold">WEIGHTED APY</p>
                <div className={`flex items-center gap-1 ${getTrendColor(metrics.weightedPortfolioAPY, metrics30DaysAgo.weightedAPY)}`}>
                  {getTrendIcon(metrics.weightedPortfolioAPY, metrics30DaysAgo.weightedAPY)}
                </div>
              </div>
              <p className="text-white text-xl sm:text-3xl font-bold">{formatPercent(metrics.weightedPortfolioAPY)}</p>
              <p className="text-indigo-300 text-[9px] sm:text-xs mt-1">
                {Math.abs(metrics.weightedPortfolioAPY - metrics30DaysAgo.weightedAPY) > 0.01 
                  ? `${metrics.weightedPortfolioAPY > metrics30DaysAgo.weightedAPY ? '+' : ''}${(metrics.weightedPortfolioAPY - metrics30DaysAgo.weightedAPY).toFixed(2)}% vs 30d ago`
                  : 'No change vs 30d'}
              </p>
            </Card>

            {/* Idle % / Liquidity */}
            <Card className="bg-gradient-to-br from-cyan-900/80 to-cyan-800/80 border-cyan-600 p-3 sm:p-4 backdrop-blur-sm">
              <div className="flex items-center justify-between mb-1">
                <p className="text-cyan-200 text-[10px] sm:text-xs font-semibold">IDLE % / LIQUIDITY</p>
                <div className={getTrendColor(metrics.idlePercent, metrics30DaysAgo.idlePercent, false)}>
                  {getTrendIcon(metrics.idlePercent, metrics30DaysAgo.idlePercent)}
                </div>
              </div>
              <p className="text-white text-xl sm:text-3xl font-bold">{formatPercent(metrics.idlePercent)}</p>
              <p className={`text-[9px] sm:text-xs mt-1 ${
                metrics.idlePercent >= 5 && metrics.idlePercent <= 10 ? 'text-green-300' : 
                metrics.idlePercent > 10 ? 'text-amber-300' : 'text-red-300'
              }`}>
                Target: 5-10%
              </p>
            </Card>

            {/* HHI + Max Exposure */}
            <Card className="bg-gradient-to-br from-orange-900/80 to-orange-800/80 border-orange-600 p-3 sm:p-4 backdrop-blur-sm">
              <div className="flex items-center justify-between mb-1">
                <p className="text-orange-200 text-[10px] sm:text-xs font-semibold">HHI / MAX EXPOSURE</p>
                <div className={getTrendColor(counterpartyRisk.hhi, metrics30DaysAgo.hhi, false)}>
                  {getTrendIcon(counterpartyRisk.hhi, metrics30DaysAgo.hhi)}
                </div>
              </div>
              <p className="text-white text-lg sm:text-2xl font-bold">{formatNumber(counterpartyRisk.hhi)}</p>
              <p className="text-orange-300 text-[9px] sm:text-xs mt-1">
                Max: {formatPercent(counterpartyRisk.maxSingleExposure)}
              </p>
            </Card>

            {/* Investor Stability */}
            <Card className="bg-gradient-to-br from-purple-900/80 to-purple-800/80 border-purple-600 p-3 sm:p-4 backdrop-blur-sm">
              <div className="flex items-center justify-between mb-1">
                <p className="text-purple-200 text-[10px] sm:text-xs font-semibold">INVESTOR STABILITY</p>
                <div className={getTrendColor(investorStabilityPercent, metrics30DaysAgo.investorStabilityPercent)}>
                  {getTrendIcon(investorStabilityPercent, metrics30DaysAgo.investorStabilityPercent)}
                </div>
              </div>
              <p className="text-white text-xl sm:text-3xl font-bold">{formatPercent(investorStabilityPercent)}</p>
              <p className="text-purple-300 text-[9px] sm:text-xs mt-1">
                {investorIntelligence.filter(i => i.redemptionRisk).length} at risk
              </p>
            </Card>

            {/* Portfolio Health Score */}
            <Card className="bg-gradient-to-br from-emerald-900/80 to-emerald-800/80 border-emerald-600 p-3 sm:p-4 backdrop-blur-sm">
              <div className="flex items-center justify-between mb-1">
                <p className="text-emerald-200 text-[10px] sm:text-xs font-semibold">HEALTH SCORE</p>
                <div className={getTrendColor(portfolioHealthScore.overall, metrics30DaysAgo.healthScore)}>
                  {getTrendIcon(portfolioHealthScore.overall, metrics30DaysAgo.healthScore)}
                </div>
              </div>
              <p className="text-white text-xl sm:text-3xl font-bold">{portfolioHealthScore.overall.toFixed(0)}</p>
              <p className={`text-[9px] sm:text-xs mt-1 ${
                portfolioHealthScore.overall >= 70 ? 'text-green-300' : 
                portfolioHealthScore.overall >= 50 ? 'text-amber-300' : 'text-red-300'
              }`}>
                {portfolioHealthScore.overall >= 70 ? 'Excellent' : portfolioHealthScore.overall >= 50 ? 'Good' : 'Needs Attention'}
              </p>
            </Card>
          </div>
        </div>

        {/* System Alerts - High Priority */}
        {systemAlerts.length > 0 && (
          <Card className="bg-gradient-to-br from-red-900/20 to-amber-900/20 border-red-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
            <div className="flex items-center gap-2 mb-4">
              <Bell className="w-5 h-5 text-red-400" />
              <h3 className="text-white text-base sm:text-lg font-semibold">Active Alerts ({systemAlerts.length})</h3>
            </div>
            <AlertsDashboard alerts={systemAlerts} />
          </Card>
        )}

        {/* Predictive Analytics Summary */}
        <Card className="bg-gradient-to-br from-violet-900/30 to-purple-900/30 border-violet-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-4">
            <Target className="w-5 h-5 text-violet-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Next 30 Days Forecast</h3>
          </div>
          
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
            <div className="bg-slate-800/50 rounded-lg p-4">
              <p className="text-slate-400 text-xs mb-1">Expected Profit</p>
              <p className="text-emerald-400 text-2xl font-bold">{formatCurrency(predictiveAnalytics.totalExpectedProfit30d)}</p>
            </div>
            <div className="bg-slate-800/50 rounded-lg p-4">
              <p className="text-slate-400 text-xs mb-1">Capital Returning</p>
              <p className="text-cyan-400 text-2xl font-bold">{formatCurrency(fundingGapForecast.totalReturning)}</p>
            </div>
            <div className="bg-slate-800/50 rounded-lg p-4">
              <p className="text-slate-400 text-xs mb-1">Max Deployable</p>
              <p className="text-purple-400 text-2xl font-bold">{formatCurrency(predictiveAnalytics.maxDeploymentCapacity)}</p>
            </div>
          </div>
          
          <Card className="bg-violet-800/30 border-violet-700/50 p-4">
            <p className="text-violet-200 text-sm font-semibold mb-2">Reallocation Suggestion</p>
            <p className="text-white text-sm mb-2">
              <strong>{formatCurrency(predictiveAnalytics.reallocationSuggestion.returningAmount)} AED</strong> available for deployment from next 10 deals
            </p>
            <p className="text-violet-300 text-xs mb-2">Recommend deploying to top performers (APY + quality score, within exposure limits):</p>
            <div className="flex flex-wrap gap-2">
              {predictiveAnalytics.reallocationSuggestion.suggestedCustomers.map((customer, idx) => (
                <Badge key={idx} className="bg-violet-500/20 text-violet-300 border-violet-500/50 text-xs">
                  {customer}
                </Badge>
              ))}
            </div>
          </Card>
        </Card>

        {/* Yield Intelligence */}
        <Card className="bg-gradient-to-br from-indigo-900/30 to-violet-900/30 border-indigo-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-4">
            <Zap className="w-5 h-5 text-indigo-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Yield Intelligence</h3>
          </div>
          
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <Card className="bg-indigo-800/50 border-indigo-700 p-4">
              <p className="text-indigo-200 text-xs font-medium mb-2">Weighted Portfolio APY</p>
              <p className="text-white text-2xl font-bold mb-1">{formatPercent(metrics.weightedPortfolioAPY)}</p>
              <p className="text-indigo-300 text-xs">Current ongoing exposure</p>
            </Card>

            <Card className="bg-emerald-800/50 border-emerald-700 p-4">
              <p className="text-emerald-200 text-xs font-medium mb-2">Top Performer (APY)</p>
              {sortedCustomerAnalysis.filter(c => c.total >= 3).length > 0 ? (
                <>
                  <p className="text-white text-lg font-bold mb-1">{sortedCustomerAnalysis.filter(c => c.total >= 3)[0].name}</p>
                  <p className="text-emerald-300 text-sm">{formatPercent(sortedCustomerAnalysis.filter(c => c.total >= 3)[0].perAnnumProfitPercent)}</p>
                  <p className="text-emerald-400 text-xs mt-1">{sortedCustomerAnalysis.filter(c => c.total >= 3)[0].total} deals</p>
                </>
              ) : (
                <p className="text-slate-400 text-sm">Min 3 deals required</p>
              )}
            </Card>

            <Card className="bg-red-800/50 border-red-700 p-4">
              <p className="text-red-200 text-xs font-medium mb-2">Bottom Performer (APY)</p>
              {sortedCustomerAnalysis.filter(c => c.total >= 3).length > 0 ? (
                <>
                  <p className="text-white text-lg font-bold mb-1">{sortedCustomerAnalysis.filter(c => c.total >= 3)[sortedCustomerAnalysis.filter(c => c.total >= 3).length - 1].name}</p>
                  <p className="text-red-300 text-sm">{formatPercent(sortedCustomerAnalysis.filter(c => c.total >= 3)[sortedCustomerAnalysis.filter(c => c.total >= 3).length - 1].perAnnumProfitPercent)}</p>
                  <p className="text-red-400 text-xs mt-1">{sortedCustomerAnalysis.filter(c => c.total >= 3)[sortedCustomerAnalysis.filter(c => c.total >= 3).length - 1].total} deals</p>
                </>
              ) : (
                <p className="text-slate-400 text-sm">Min 3 deals required</p>
              )}
            </Card>
          </div>
        </Card>

        {/* Counterparty Risk Intelligence */}
        <Card className="bg-gradient-to-br from-red-900/30 to-orange-900/30 border-red-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-6">
            <Shield className="w-5 h-5 text-red-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Counterparty Risk Intelligence</h3>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
            <Card className="bg-orange-800/50 border-orange-700 p-4">
              <p className="text-orange-200 text-xs font-medium mb-2">HHI (Concentration)</p>
              <p className="text-white text-2xl font-bold mb-1">{formatNumber(counterpartyRisk.hhi)}</p>
              <p className="text-orange-300 text-xs">
                {counterpartyRisk.hhi < 1500 ? "✅ Low" :
                 counterpartyRisk.hhi < 2500 ? "⚠️ Moderate" :
                 "🔴 High"}
              </p>
            </Card>

            <Card className="bg-red-800/50 border-red-700 p-4">
              <p className="text-red-200 text-xs font-medium mb-2">Max Single Exposure</p>
              <p className="text-white text-2xl font-bold mb-1">{formatPercent(counterpartyRisk.maxSingleExposure)}</p>
              <p className="text-red-300 text-xs">Of ongoing exposure</p>
            </Card>

            <Card className="bg-amber-800/50 border-amber-700 p-4">
              <p className="text-amber-200 text-xs font-medium mb-2">Top 3 Profit Share</p>
              <p className="text-white text-2xl font-bold mb-1">{formatPercent(counterpartyRisk.top3ProfitPercent)}</p>
              <p className="text-amber-300 text-xs">Of total profit</p>
            </Card>

            <Card className="bg-yellow-800/50 border-yellow-700 p-4">
              <p className="text-yellow-200 text-xs font-medium mb-2">Risk Assessment</p>
              <p className="text-white text-lg font-bold mb-1">
                {counterpartyRisk.hhi < 1500 && counterpartyRisk.top3ProfitPercent < 60 ? "Low Risk" :
                 counterpartyRisk.hhi < 2500 && counterpartyRisk.top3ProfitPercent < 75 ? "Medium Risk" :
                 "High Risk"}
              </p>
              <p className="text-yellow-300 text-xs">Based on HHI & Top 3</p>
            </Card>
          </div>

          <div>
            <h4 className="text-white text-sm font-semibold mb-3">Customer Quality Scores (Min 3 Deals)</h4>
            <div className="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden max-h-80 overflow-y-auto">
              <Table>
                <TableHeader>
                  <TableRow className="border-slate-700">
                    <TableHead className="text-slate-300 text-xs">Customer</TableHead>
                    <TableHead className="text-slate-300 text-xs text-center">Deals</TableHead>
                    <TableHead className="text-slate-300 text-xs text-right">Quality Score</TableHead>
                    <TableHead className="text-slate-300 text-xs text-right">APY %</TableHead>
                    <TableHead className="text-slate-300 text-xs text-right hidden sm:table-cell">Avg Tenor</TableHead>
                    <TableHead className="text-slate-300 text-xs text-right hidden md:table-cell">Exposure %</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {customerQualityScores.slice(0, 15).map((customer, idx) => (
                    <TableRow key={idx} className="border-slate-700/50">
                      <TableCell className="text-white font-medium text-xs">{customer.customer}</TableCell>
                      <TableCell className="text-center text-slate-300 text-xs">{customer.dealCount}</TableCell>
                      <TableCell className="text-right">
                        <Badge className={
                          customer.qualityScore >= 75 ? "bg-green-500/20 text-green-400 border-green-500/50 text-xs" :
                          customer.qualityScore >= 50 ? "bg-yellow-500/20 text-yellow-400 border-yellow-500/50 text-xs" :
                          "bg-red-500/20 text-red-400 border-red-500/50 text-xs"
                        }>
                          {formatNumber(customer.qualityScore)}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right text-purple-400 font-semibold text-xs">{formatPercent(customer.perAnnumProfit)}</TableCell>
                      <TableCell className="text-right text-cyan-400 text-xs hidden sm:table-cell">{formatNumber(customer.avgTenor)}d</TableCell>
                      <TableCell className="text-right text-orange-400 text-xs hidden md:table-cell">{formatPercent(customer.exposurePercent)}</TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        </Card>

        {/* Investor Intelligence */}
        <Card className="bg-gradient-to-br from-purple-900/30 to-pink-900/30 border-purple-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-6">
            <Users className="w-5 h-5 text-purple-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Investor Intelligence</h3>
          </div>

          <div className="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden mb-4 max-h-96 overflow-y-auto">
            <Table>
              <TableHeader>
                <TableRow className="border-slate-700">
                  <TableHead className="text-slate-300 text-xs">Investor</TableHead>
                  <TableHead className="text-slate-300 text-xs text-right">Yield %</TableHead>
                  <TableHead className="text-slate-300 text-xs text-right hidden sm:table-cell">Deployed / Total</TableHead>
                  <TableHead className="text-slate-300 text-xs text-right hidden md:table-cell">Days Since Top-Up</TableHead>
                  <TableHead className="text-slate-300 text-xs text-center">Risk</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {investorIntelligence.map((investor, idx) => (
                  <TableRow key={idx} className="border-slate-700/50">
                    <TableCell className="text-white font-medium text-xs">{investor.name}</TableCell>
                    <TableCell className="text-right text-emerald-400 font-semibold text-xs">{formatPercent(investor.weightedYield)}</TableCell>
                    <TableCell className="text-right text-slate-300 text-xs hidden sm:table-cell">
                      <span className="text-purple-400 font-mono">{formatCurrency(investor.deployedCapital)}</span>
                      <span className="text-slate-500"> / </span>
                      <span className="text-cyan-400 font-mono">{formatCurrency(investor.totalCapital)}</span>
                      <span className="text-slate-500 ml-1">({formatPercent(investor.deploymentRate)})</span>
                    </TableCell>
                    <TableCell className="text-right text-slate-400 text-xs hidden md:table-cell">{investor.daysSinceLastInvestment}d</TableCell>
                    <TableCell className="text-center">
                      <Badge className={
                        investor.redemptionRisk 
                          ? "bg-red-500/20 text-red-400 border-red-500/50 text-xs" 
                          : "bg-green-500/20 text-green-400 border-green-500/50 text-xs"
                      }>
                        {investor.redemptionRisk ? "⚠️ High" : "✅ Low"}
                      </Badge>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <Card className="bg-purple-800/50 border-purple-700 p-3">
              <p className="text-purple-200 text-xs mb-1">At Redemption Risk</p>
              <p className="text-white text-xl font-bold">{investorIntelligence.filter(i => i.redemptionRisk).length}</p>
              <p className="text-purple-300 text-xs mt-1">No profit in 90d + returns &lt;3%</p>
            </Card>
            <Card className="bg-pink-800/50 border-pink-700 p-3">
              <p className="text-pink-200 text-xs mb-1">Avg Deployment Rate</p>
              <p className="text-white text-xl font-bold">
                {formatPercent(investorIntelligence.reduce((sum, i) => sum + i.deploymentRate, 0) / investorIntelligence.length)}
              </p>
              <p className="text-pink-300 text-xs mt-1">Capital utilization</p>
            </Card>
            <Card className="bg-indigo-800/50 border-indigo-700 p-3">
              <p className="text-indigo-200 text-xs mb-1">Total Investor Capital</p>
              <p className="text-white text-xl font-bold">
                {formatCurrency(investorIntelligence.reduce((sum, i) => sum + i.totalCapital, 0))}
              </p>
              <p className="text-indigo-300 text-xs mt-1">AED</p>
            </Card>
          </div>
        </Card>

        {/* Deal Performance Diagnostics */}
        <Card className="bg-gradient-to-br from-emerald-900/30 to-teal-900/30 border-emerald-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-6">
            <BarChart3 className="w-5 h-5 text-emerald-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Deal Performance Diagnostics</h3>
          </div>

          <div className="mb-6">
            <h4 className="text-white text-sm font-semibold mb-3">Profit % Sweet Spot by Tenor</h4>
            <div className="grid grid-cols-5 gap-2">
              {dealPerformanceDiagnostics.tenorProfitCurve.map((bucket, idx) => {
                const maxProfit = Math.max(...dealPerformanceDiagnostics.tenorProfitCurve.map(b => b.avgProfit));
                const heightPercent = maxProfit > 0 ? (bucket.avgProfit / maxProfit) * 100 : 0;
                
                return (
                  <div key={idx} className="flex flex-col items-center">
                    <div className="w-full bg-slate-800 rounded-t-lg h-32 flex flex-col justify-end relative group">
                      <div 
                        className="w-full bg-gradient-to-t from-emerald-600 to-emerald-400 rounded-t-lg transition-all"
                        style={{ height: `${heightPercent}%` }}
                      />
                      <div className="invisible group-hover:visible absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-slate-900 border border-emerald-700 rounded text-xs whitespace-nowrap z-10">
                        <p className="text-emerald-300 font-semibold">Avg: {formatPercent(bucket.avgProfit)}</p>
                        <p className="text-slate-400">Deals: {bucket.dealCount}</p>
                      </div>
                    </div>
                    <p className="text-slate-400 text-xs mt-1 text-center">{bucket.label}</p>
                    <p className="text-emerald-400 text-xs font-semibold">{formatPercent(bucket.avgProfit)}</p>
                  </div>
                );
              })}
            </div>
            <p className="text-slate-400 text-xs mt-3 text-center italic">
              Best profit margins between {dealPerformanceDiagnostics.tenorProfitCurve.reduce((max, b) => b.avgProfit > max.avgProfit ? b : max, dealPerformanceDiagnostics.tenorProfitCurve[0]).label}
            </p>
          </div>

          <div className="mb-6">
            <h4 className="text-white text-sm font-semibold mb-3">Customer Profit Consistency (Most Volatile)</h4>
            <div className="bg-slate-800/50 border border-slate-700 rounded-lg overflow-hidden max-h-64 overflow-y-auto">
              <Table>
                <TableHeader>
                  <TableRow className="border-slate-700">
                    <TableHead className="text-slate-300 text-xs">Customer</TableHead>
                    <TableHead className="text-slate-300 text-xs text-right">Std Dev</TableHead>
                    <TableHead className="text-slate-300 text-xs text-right">Avg Profit %</TableHead>
                    <TableHead className="text-slate-300 text-xs text-center">Deals</TableHead>
                    <TableHead className="text-slate-300 text-xs text-center">Consistency</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {dealPerformanceDiagnostics.customerVariance.slice(0, 10).map((customer, idx) => (
                    <TableRow key={idx} className="border-slate-700/50">
                      <TableCell className="text-white font-medium text-xs">{customer.customer}</TableCell>
                      <TableCell className="text-right text-amber-400 text-xs">{formatPercent(customer.stdDev)}</TableCell>
                      <TableCell className="text-right text-emerald-400 text-xs">{formatPercent(customer.avgProfit)}</TableCell>
                      <TableCell className="text-center text-slate-300 text-xs">{customer.dealCount}</TableCell>
                      <TableCell className="text-center">
                        <Badge className={
                          customer.stdDev < 1 ? "bg-green-500/20 text-green-400 border-green-500/50 text-xs" :
                          customer.stdDev < 3 ? "bg-yellow-500/20 text-yellow-400 border-yellow-500/50 text-xs" :
                          "bg-red-500/20 text-red-400 border-red-500/50 text-xs"
                        }>
                          {customer.stdDev < 1 ? "High" : customer.stdDev < 3 ? "Medium" : "Low"}
                        </Badge>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <Card className="bg-green-800/50 border-green-700 p-4">
              <p className="text-green-200 text-xs font-medium mb-2">Profit After OPEX</p>
              <p className="text-white text-2xl font-bold mb-1">{formatCurrency(dealPerformanceDiagnostics.profitAfterOPEX)}</p>
              <p className="text-green-300 text-xs">Net operational profit</p>
            </Card>

            <Card className="bg-amber-800/50 border-amber-700 p-4">
              <p className="text-amber-200 text-xs font-medium mb-2">OPEX-to-Profit Ratio</p>
              <p className="text-white text-2xl font-bold mb-1">{formatPercent(dealPerformanceDiagnostics.opexToProfitRatio)}</p>
              <p className="text-amber-300 text-xs">
                {dealPerformanceDiagnostics.opexToProfitRatio < 20 ? "✅ Excellent" :
                 dealPerformanceDiagnostics.opexToProfitRatio < 40 ? "⚠️ Good" :
                 "🔴 Review needed"}
              </p>
            </Card>

            <Card className="bg-rose-800/50 border-rose-700 p-4">
              <p className="text-rose-200 text-xs font-medium mb-2">Monthly OPEX Burn</p>
              <p className="text-white text-2xl font-bold mb-1">{formatCurrency(dealPerformanceDiagnostics.monthlyBurn)}</p>
              <p className="text-rose-300 text-xs">Avg last 3 months</p>
            </Card>

            <Card className="bg-purple-800/50 border-purple-700 p-4">
              <p className="text-purple-200 text-xs font-medium mb-2">Projected Annual OPEX</p>
              <p className="text-white text-2xl font-bold mb-1">{formatCurrency(dealPerformanceDiagnostics.projectedAnnualOPEX)}</p>
              <p className="text-purple-300 text-xs">Monthly × 12</p>
            </Card>
          </div>
        </Card>

        {/* Liquidity & Deployment Efficiency */}
        <Card className="bg-gradient-to-br from-cyan-900/30 to-blue-900/30 border-cyan-700/50 p-4 sm:p-6 mb-6 sm:mb-8">
          <div className="flex items-center gap-2 mb-6">
            <Droplets className="w-5 h-5 text-cyan-400" />
            <h3 className="text-white text-base sm:text-lg font-semibold">Liquidity & Deployment Efficiency</h3>
          </div>

          <div className="mb-6">
            <h4 className="text-white text-sm font-semibold mb-3">Capital Locked - Next 12 Weeks</h4>
            <div className="grid grid-cols-6 sm:grid-cols-12 gap-2">
              {deploymentCurve.map((week) => {
                const maxLocked = Math.max(...deploymentCurve.map(w => w.lockedCapital));
                const heightPercent = maxLocked > 0 ? (week.lockedCapital / maxLocked) * 100 : 0;
                
                return (
                  <div key={week.weekNumber} className="flex flex-col items-center">
                    <div className="w-full bg-slate-800 rounded-t-lg h-32 flex flex-col justify-end relative group">
                      <div 
                        className="w-full bg-gradient-to-t from-cyan-600 to-cyan-400 rounded-t-lg transition-all"
                        style={{ height: `${heightPercent}%` }}
                      />
                      <div className="invisible group-hover:visible absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-slate-900 border border-cyan-700 rounded text-xs whitespace-nowrap z-10">
                        <p className="text-cyan-300 font-semibold">{formatCurrency(week.lockedCapital)}</p>
                      </div>
                    </div>
                    <p className="text-slate-400 text-[9px] sm:text-xs mt-1 text-center">{week.label}</p>
                  </div>
                );
              })}
            </div>
          </div>

          <div>
            <h4 className="text-white text-sm font-semibold mb-3">Idle Capital % - Last 12 Months</h4>
            <div className="grid grid-cols-6 sm:grid-cols-12 gap-2">
              {idlePercentByMonth.map((month, idx) => {
                const getHeatColor = (percent) => {
                  if (percent > 40) return 'from-red-600 to-red-500';
                  if (percent > 25) return 'from-orange-600 to-orange-500';
                  if (percent > 15) return 'from-yellow-600 to-yellow-500';
                  return 'from-green-600 to-green-500';
                };
                
                return (
                  <div key={idx} className="flex flex-col items-center group relative">
                    <div className={`w-full h-20 bg-gradient-to-t ${getHeatColor(month.idlePercent)} rounded-lg flex items-center justify-center`}>
                      <p className="text-white text-xs sm:text-sm font-bold">{formatPercent(month.idlePercent)}</p>
                    </div>
                    <p className="text-slate-400 text-[9px] sm:text-xs mt-1 text-center">{month.month}</p>
                    
                    <div className="invisible group-hover:visible absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-slate-900 border border-cyan-700 rounded text-xs whitespace-nowrap z-10">
                      <p className="text-slate-300">Fund: {formatCurrency(month.totalFund)}</p>
                      <p className="text-slate-300">Locked: {formatCurrency(month.avgLocked)}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        </Card>
      </div>
    </div>
  );
}