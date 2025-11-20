import React, { useMemo, useEffect, useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { ArrowLeft, TrendingUp, Wallet, PiggyBank, Target, Calculator, Activity, Info, ChevronDown, ChevronUp, Zap, AlertCircle } from "lucide-react";
import { format } from "date-fns";
import { Link, useNavigate } from "react-router-dom";
import { createPageUrl } from "@/utils";

// Generate consistent hash from string (must match Investors.jsx)
const generateInvestorHash = (name) => {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    const char = name.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash;
  }
  return Math.abs(hash).toString(36);
};

export default function InvestorDashboardPage() {
  const urlParams = new URLSearchParams(window.location.search);
  const investorIdFromUrl = urlParams.get('id');
  const navigate = useNavigate();

  const [currentUser, setCurrentUser] = useState(null);
  const [loadingUser, setLoadingUser] = useState(true);
  const [accessDenied, setAccessDenied] = useState(false);
  const [investorUser, setInvestorUser] = useState(null);
  const [investorName, setInvestorName] = useState(null);
  const [performanceData, setPerformanceData] = useState(null);
  const [loadingPerformance, setLoadingPerformance] = useState(false);
  const [performanceError, setPerformanceError] = useState(null);
  
  // State for lazy-loaded forecasts
  const [forecastsData, setForecastsData] = useState({
    baseline: null,
    conservative: null,
    investor_specific: null
  });
  
  // State for expanded sections
  const [expandedSections, setExpandedSections] = useState({
    conservative: false,
    investor_specific: false
  });
  
  // State for loading individual forecast sections
  const [loadingSections, setLoadingSections] = useState({
    conservative: false,
    investor_specific: false
  });
  
  const [showAllInvestments, setShowAllInvestments] = useState(false);
  const [showAllAllocations, setShowAllAllocations] = useState(false);

  const USD_RATE = 3.67;

  // Utility functions
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const formatIntegerCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(value);
  };

  const formatDate = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy');
  };

  const formatPercentage = (value) => {
    return value.toFixed(2) + '%';
  };

  // Query to get all investments (needed to map hash to name)
  const { data: allInvestments } = useQuery({
    queryKey: ['allInvestmentsForMapping'],
    queryFn: () => base44.entities.Investment.list(),
    initialData: [],
  });

  // Fetch current user and validate access
  useEffect(() => {
    const fetchUser = async () => {
      try {
        const user = await base44.auth.me();
        setCurrentUser(user);

        if (!investorIdFromUrl) {
          setAccessDenied(true);
          setLoadingUser(false);
          return;
        }

        // Admin can view any investor dashboard
        if (user.role === 'admin') {
          // First, check if this is a user's investor_id
          const users = await base44.entities.User.filter({ investor_id: investorIdFromUrl });
          
          if (users.length > 0) {
            // Found a user with this investor_id
            setInvestorUser(users[0]);
            setInvestorName(users[0].investor_name || users[0].full_name);
          } else {
            // No user found, try to find investor by hash using allInvestments data
            const allInvestorNames = [...new Set(allInvestments.map(inv => inv.name))];
            const matchingInvestor = allInvestorNames.find(name => 
              generateInvestorHash(name) === investorIdFromUrl
            );
            
            if (matchingInvestor) {
              setInvestorName(matchingInvestor);
              // No investorUser in this case
            } else {
              setAccessDenied(true);
            }
          }
        } else {
          // Regular user - can only view their own dashboard
          if (investorIdFromUrl !== user.investor_id) {
            setAccessDenied(true);
          } else {
            setInvestorUser(user);
            setInvestorName(user.investor_name || user.full_name);
          }
        }
      } catch (error) {
        console.error("Error loading investor data:", error);
        setAccessDenied(true);
      } finally {
        setLoadingUser(false);
      }
    };
    fetchUser();
  }, [investorIdFromUrl, navigate, allInvestments]);

  // Fetch performance data and ONLY baseline forecast initially
  useEffect(() => {
    const fetchPerformanceData = async () => {
      if (!investorName) return;
      
      console.log('Fetching performance data for investor:', investorName);
      setLoadingPerformance(true);
      setPerformanceError(null);
      
      try {
        // Fetch ONLY baseline performance data initially
        const response = await base44.functions.invoke('calculateInvestorPerformance', { 
          investor_name: investorName,
          forecast_rate_option: 'baseline'
        });
        
        if (response.data.success) {
          console.log('Performance data received:', response.data);
          setPerformanceData(response.data);
          
          // Store baseline forecast
          setForecastsData(prev => ({
            ...prev,
            baseline: response.data
          }));
        } else {
          setPerformanceError(response.data.error || 'Failed to fetch performance data');
        }
      } catch (error) {
        console.error("Error fetching performance data:", error);
        setPerformanceError(error.message || 'An error occurred while fetching performance data');
      } finally {
        setLoadingPerformance(false);
      }
    };
    
    fetchPerformanceData();
  }, [investorName]);

  // Lazy load specific forecast when section is expanded
  const toggleForecastSection = async (section) => {
    const isCurrentlyExpanded = expandedSections[section];
    
    // Toggle the expanded state
    setExpandedSections(prev => ({
      ...prev,
      [section]: !isCurrentlyExpanded
    }));
    
    // If expanding and data not loaded yet, fetch it
    if (!isCurrentlyExpanded && !forecastsData[section]) {
      setLoadingSections(prev => ({ ...prev, [section]: true }));
      
      try {
        const response = await base44.functions.invoke('calculateInvestorPerformance', { 
          investor_name: investorName,
          forecast_rate_option: section
        });
        
        if (response.data.success) {
          setForecastsData(prev => ({
            ...prev,
            [section]: response.data
          }));
        }
      } catch (error) {
        console.error(`Error fetching ${section} forecast:`, error);
      } finally {
        setLoadingSections(prev => ({ ...prev, [section]: false }));
      }
    }
  };

  // Use investorName for all queries
  const { data: investments, isLoading: investmentsLoading } = useQuery({
    queryKey: ['investments', investorName],
    queryFn: () => base44.entities.Investment.filter({ name: investorName }),
    initialData: [],
    enabled: !!investorName && !accessDenied,
  });

  const { data: allocations, isLoading: allocationsLoading } = useQuery({
    queryKey: ['profitAllocations', investorName],
    queryFn: () => base44.entities.ProfitAllocation.filter({ investor_name: investorName }, '-transaction_number'),
    initialData: [],
    enabled: !!investorName && !accessDenied,
  });

  // Calculate summary statistics
  const summary = useMemo(() => {
    const totalPrincipal = investments.reduce((sum, inv) => sum + inv.amount, 0);
    const realizedProfit = allocations
      .filter(alloc => alloc.deal_status === "Ended")
      .reduce((sum, alloc) => sum + alloc.individual_profit, 0);
    const expectedProfit = allocations
      .filter(alloc => alloc.deal_status === "Ongoing")
      .reduce((sum, alloc) => sum + alloc.individual_profit, 0);
    const totalProfit = realizedProfit + expectedProfit;
    const totalCapital = totalPrincipal + realizedProfit;
    const profitPercentage = totalPrincipal > 0 ? (totalProfit / totalPrincipal) * 100 : 0;

    return {
      totalPrincipal,
      realizedProfit,
      expectedProfit,
      totalProfit,
      totalCapital,
      profitPercentage,
    };
  }, [investments, allocations]);

  // Show loading state
  if (loadingUser) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  // Show access denied
  if (accessDenied) {
    return (
      <div className="p-6">
        <div className="max-w-7xl mx-auto">
          <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
            <div className="text-6xl mb-4">🔒</div>
            <h2 className="text-2xl font-bold text-white mb-2">Access Denied</h2>
            <p className="text-slate-400 text-lg mb-6">
              You do not have permission to view this investor's dashboard.
            </p>
            {currentUser?.role === 'admin' ? (
              <Link to={createPageUrl('Investors')}>
                <Button className="bg-blue-600 hover:bg-blue-700">
                  Go to Investors
                </Button>
              </Link>
            ) : (
              <Button
                onClick={() => navigate(createPageUrl('InvestorDashboard') + '?id=' + currentUser.investor_id)}
                className="bg-blue-600 hover:bg-blue-700"
              >
                Go to My Dashboard
              </Button>
            )}
          </Card>
        </div>
      </div>
    );
  }

  // If no investor name could be determined
  if (!investorName) {
    return (
      <div className="p-6">
        <div className="max-w-7xl mx-auto">
          <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
            <p className="text-slate-400 text-lg">No investor found</p>
            {currentUser?.role === 'admin' && (
              <Link to={createPageUrl('Investments')}>
                <Button className="mt-4 bg-blue-600 hover:bg-blue-700">
                  Go to Investments
                </Button>
              </Link>
            )}
          </Card>
        </div>
      </div>
    );
  }

  const isLoading = investmentsLoading || allocationsLoading;

  // For displaying limited rows
  const displayedInvestments = showAllInvestments ? investments : investments.slice(-5);
  const displayedAllocations = showAllAllocations ? allocations : allocations.slice(0, 5);

  // Dynamic tooltip and insights based on comparison
  const isInvestorRateHigher = performanceData && 
    parseFloat(performanceData.investorSpecificAnnualizedReturn) > parseFloat(performanceData.platformBaselineRate);

  return (
    <div className="p-3 sm:p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-6 sm:mb-8">
          {currentUser?.role === 'admin' && (
            <Link to={createPageUrl('Investors')}>
              <Button variant="ghost" className="text-slate-400 hover:text-white mb-4 text-sm">
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back to Investors
              </Button>
            </Link>
          )}
          <div className="flex items-center gap-2 sm:gap-3">
            <div className="p-2 sm:p-3 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl">
              <Wallet className="w-6 h-6 sm:w-8 sm:h-8 text-white" />
            </div>
            <div>
              <h2 className="text-2xl sm:text-3xl font-bold text-white">{investorName}</h2>
              <p className="text-slate-400 mt-1 text-xs sm:text-base">Investor Portfolio Dashboard</p>
            </div>
          </div>
        </div>

        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
          </div>
        ) : (
          <>
            {/* Summary Statistics Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
              {/* Total Principal */}
              <Card className="relative overflow-hidden bg-gradient-to-br from-blue-800 to-blue-900 border-blue-700">
                <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-blue-500 rounded-full opacity-10" />
                <div className="p-4 sm:p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <p className="text-blue-200 text-xs sm:text-sm font-medium mb-1">Total Principal</p>
                      <p className="text-white text-2xl sm:text-3xl font-bold">{formatCurrency(summary.totalPrincipal)}</p>
                      <p className="text-blue-200 text-xs mt-1">AED / USD {formatCurrency(summary.totalPrincipal / USD_RATE)}</p>
                    </div>
                    <div className="p-2 sm:p-3 bg-blue-500/20 rounded-xl">
                      <PiggyBank className="w-5 h-5 sm:w-6 sm:h-6 text-blue-400" />
                    </div>
                  </div>
                </div>
              </Card>

              {/* Total Capital */}
              <Card className="relative overflow-hidden bg-gradient-to-br from-purple-800 to-purple-900 border-purple-700">
                <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-purple-500 rounded-full opacity-10" />
                <div className="p-4 sm:p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <p className="text-purple-200 text-xs sm:text-sm font-medium mb-1">Capital Balance</p>
                      <p className="text-white text-2xl sm:text-3xl font-bold">{formatCurrency(summary.totalCapital)}</p>
                      <p className="text-purple-200 text-xs mt-1">AED / USD {formatCurrency(summary.totalCapital / USD_RATE)}</p>
                    </div>
                    <div className="p-2 sm:p-3 bg-purple-500/20 rounded-xl">
                      <Calculator className="w-5 h-5 sm:w-6 sm:h-6 text-purple-400" />
                    </div>
                  </div>
                </div>
              </Card>

              {/* Total Profit */}
              <Card className="relative overflow-hidden bg-gradient-to-br from-emerald-800 to-emerald-900 border-emerald-700">
                <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-emerald-500 rounded-full opacity-10" />
                <div className="p-4 sm:p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <p className="text-emerald-200 text-xs sm:text-sm font-medium mb-1">Total Profit</p>
                      <p className="text-white text-2xl sm:text-3xl font-bold">{formatCurrency(summary.totalProfit)}</p>
                      <p className="text-emerald-200 text-xs mt-1">AED / {formatPercentage(summary.profitPercentage)} ROI</p>
                    </div>
                    <div className="p-2 sm:p-3 bg-emerald-500/20 rounded-xl">
                      <TrendingUp className="w-5 h-5 sm:w-6 sm:h-6 text-emerald-400" />
                    </div>
                  </div>
                </div>
              </Card>

              {/* Realized Profit */}
              <Card className="relative overflow-hidden bg-gradient-to-br from-green-800 to-green-900 border-green-700">
                <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-green-500 rounded-full opacity-10" />
                <div className="p-4 sm:p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <p className="text-green-200 text-xs sm:text-sm font-medium mb-1">Realized Profit</p>
                      <p className="text-white text-2xl sm:text-3xl font-bold">{formatCurrency(summary.realizedProfit)}</p>
                      <p className="text-green-200 text-xs mt-1">AED / From ended deals</p>
                    </div>
                    <div className="p-2 sm:p-3 bg-green-500/20 rounded-xl">
                      <Target className="w-5 h-5 sm:w-6 sm:h-6 text-green-400" />
                    </div>
                  </div>
                </div>
              </Card>

              {/* Expected Profit */}
              <Card className="relative overflow-hidden bg-gradient-to-br from-amber-800 to-amber-900 border-amber-700">
                <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-amber-500 rounded-full opacity-10" />
                <div className="p-4 sm:p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <p className="text-amber-200 text-xs sm:text-sm font-medium mb-1">Expected Profit</p>
                      <p className="text-white text-2xl sm:text-3xl font-bold">{formatCurrency(summary.expectedProfit)}</p>
                      <p className="text-amber-200 text-xs mt-1">AED / From ongoing deals</p>
                    </div>
                    <div className="p-2 sm:p-3 bg-amber-500/20 rounded-xl">
                      <Target className="w-5 h-5 sm:w-6 sm:h-6 text-amber-400" />
                    </div>
                  </div>
                </div>
              </Card>

              {/* Deals Count */}
              <Card className="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700">
                <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-slate-500 rounded-full opacity-10" />
                <div className="p-4 sm:p-6">
                  <div className="flex justify-between items-start mb-4">
                    <div>
                      <p className="text-slate-300 text-xs sm:text-sm font-medium mb-1">Total Deals</p>
                      <p className="text-white text-2xl sm:text-3xl font-bold">{allocations.length}</p>
                      <p className="text-slate-400 text-xs mt-1">
                        {allocations.filter(a => a.deal_status === "Ended").length} ended, {allocations.filter(a => a.deal_status === "Ongoing").length} ongoing
                      </p>
                    </div>
                    <div className="p-2 sm:p-3 bg-slate-500/20 rounded-xl">
                      <span className="text-xl sm:text-2xl">📊</span>
                    </div>
                  </div>
                </div>
              </Card>
            </div>

            {/* Performance Metrics & Forecasts */}
            {performanceData && (
              <>
                {/* Performance Metrics */}
                <div className="mb-6 sm:mb-8">
                  <h3 className="text-lg sm:text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <Activity className="w-5 h-5 text-orange-400" />
                    Performance Metrics
                  </h3>
                  <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    {/* Time-Weighted Return with Dynamic Tooltip */}
                    <Card className="bg-gradient-to-br from-orange-800 to-orange-900 border-orange-700 p-4">
                      <div className="flex items-start justify-between mb-1">
                        <p className="text-orange-200 text-xs font-medium">Modified Dietz Return (AED)</p>
                        <div className="group relative">
                          <Info className="w-4 h-4 text-orange-300 cursor-help" />
                          <div className="invisible group-hover:visible absolute right-0 top-6 w-72 bg-slate-900 border border-orange-700 rounded-lg p-3 text-xs text-slate-300 z-10 shadow-xl">
                            <p className="font-semibold text-orange-300 mb-1">About This Metric:</p>
                            {isInvestorRateHigher ? (
                              <>
                                <p className="mb-2">
                                  Your time-weighted annualized return is currently higher than the platform baseline. 
                                  This indicates that your early investments have benefited from strong compounding effects 
                                  and favorable deal timing.
                                </p>
                                <p className="text-slate-400">
                                  This outperformance reflects the historical success of your investment timing and 
                                  the cumulative impact of realized profits.
                                </p>
                              </>
                            ) : (
                              <>
                                <p className="mb-2">
                                  This time-weighted annualized return is currently lower because recent large capital additions 
                                  haven't been invested long enough to compound and express the fund's run-rate performance.
                                </p>
                                <p className="text-slate-400">
                                  As your capital ages and generates returns over time, this figure will converge toward 
                                  the platform baseline rate.
                                </p>
                              </>
                            )}
                          </div>
                        </div>
                      </div>
                      <p className="text-white text-2xl font-bold">{performanceData.investorSpecificAnnualizedReturn}%</p>
                      <p className="text-orange-200 text-xs mt-1">p.a. (current weighting)</p>
                    </Card>

                    {/* Platform Run-Rate - DYNAMIC */}
                    <Card className="bg-gradient-to-br from-cyan-800 to-cyan-900 border-cyan-700 p-4">
                      <div className="flex items-start justify-between mb-1">
                        <p className="text-cyan-200 text-xs font-medium">Platform Run-Rate (AED)</p>
                        <Zap className="w-4 h-4 text-cyan-300" />
                      </div>
                      <p className="text-white text-2xl font-bold">{performanceData.platformBaselineRate}%</p>
                      <p className="text-cyan-200 text-xs mt-1">p.a. (dynamic fund baseline)</p>
                    </Card>

                    {/* Days Invested */}
                    <Card className="bg-gradient-to-br from-indigo-800 to-indigo-900 border-indigo-700 p-4">
                      <p className="text-indigo-200 text-xs font-medium mb-1">Days Invested</p>
                      <p className="text-white text-2xl font-bold">{performanceData.daysSinceInception}</p>
                      <p className="text-indigo-200 text-xs mt-1">{performanceData.yearsSinceInception} years since first investment</p>
                    </Card>
                  </div>
                  
                  {/* Enhanced Info Callout - Dynamic */}
                  <Card className="bg-amber-900/20 border-amber-700/50 p-4 mt-4">
                    <div className="flex items-start gap-3">
                      <Info className="w-5 h-5 text-amber-400 flex-shrink-0 mt-0.5" />
                      <div>
                        <p className="text-amber-200 text-sm font-medium mb-2">Understanding Your Returns (AED)</p>
                        <div className="space-y-2 text-amber-300/80 text-xs">
                          <p>
                            <strong className="text-amber-200">Modified Dietz ({performanceData.investorSpecificAnnualizedReturn}%):</strong> Your time-weighted 
                            annualized return accounts for when each AED investment was made. 
                            {isInvestorRateHigher ? (
                              <> Your early investments have compounded effectively, resulting in a higher return than the current platform baseline. 
                              This outperformance reflects successful investment timing and strong historical deal performance.</>
                            ) : (
                              <> Recent large investments haven't had time to compound yet, which reduces the trailing figure.</>
                            )}
                          </p>
                          <p>
                            <strong className="text-amber-200">Platform Run-Rate ({performanceData.platformBaselineRate}% - Dynamic):</strong> This is calculated 
                            dynamically from all live transaction data, representing the fund's actual annualized performance capacity. 
                            {isInvestorRateHigher ? (
                              <> While your historical return exceeds this rate, future returns will tend to align with this baseline as the fund continues to operate.</>
                            ) : (
                              <> As your capital remains invested and generates returns, your Modified Dietz figure will trend toward this rate.</>
                            )}
                          </p>
                          <p className="pt-2 border-t border-amber-700/30 text-amber-400">
                            💡 <strong>Key Insight:</strong> 
                            {isInvestorRateHigher ? (
                              <> Your current {performanceData.investorSpecificAnnualizedReturn}% reflects excellent early positioning and compounding. 
                              Expect future returns to stabilize around the {performanceData.platformBaselineRate}% platform baseline.</>
                            ) : (
                              <> Your current {performanceData.investorSpecificAnnualizedReturn}% reflects timing, not performance quality. 
                              Given time, your annualized return will converge toward the {performanceData.platformBaselineRate}% baseline.</>
                            )}
                          </p>
                        </div>
                      </div>
                    </div>
                  </Card>
                </div>

                {/* Forecasts - Lazy Loaded with Collapse */}
                <div className="mb-6 sm:mb-8">
                  <h3 className="text-lg sm:text-xl font-bold text-white mb-4 flex items-center gap-2">
                    <TrendingUp className="w-5 h-5 text-emerald-400" />
                    Investment Forecasts (Principal-Only Base in AED)
                  </h3>
                  
                  <Card className="bg-slate-800/30 border-slate-700 p-6 mb-4">
                    <div className="mb-4">
                      <p className="text-slate-300 text-sm mb-1">
                        Forecast Base: <span className="text-white font-bold">{formatCurrency(performanceData.totalPrincipal)} AED</span> (Total Principal)
                      </p>
                      <p className="text-slate-400 text-xs">
                        Projections show growth of your current principal investment (AED) using compounded returns across three scenarios.
                      </p>
                    </div>
                  </Card>

                  {/* Platform Baseline Forecast - Always Visible */}
                  {forecastsData.baseline && (
                    <Card className="bg-slate-800/30 border-slate-700 p-6 mb-4">
                      <div className="flex items-center gap-2 mb-4">
                        <div className="px-3 py-1 bg-cyan-600 rounded-lg">
                          <p className="text-white text-sm font-semibold">Platform Baseline ({forecastsData.baseline.platformBaselineRate}%)</p>
                        </div>
                      </div>
                      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                        {Object.values(forecastsData.baseline.forecasts).map((forecast) => (
                          <div key={forecast.months} className="text-center">
                            <p className="text-slate-400 text-xs mb-2">{forecast.months} Months</p>
                            <p className="text-white text-xl font-bold">{formatCurrency(forecast.projectedValue)}</p>
                            <p className="text-emerald-400 text-xs mt-1">+{formatCurrency(forecast.projectedGain)}</p>
                            <p className="text-slate-500 text-xs">({forecast.projectedROI}%)</p>
                          </div>
                        ))}
                      </div>
                    </Card>
                  )}

                  {/* Conservative Forecast - Collapsible & Lazy Loaded */}
                  <Card className="bg-slate-800/30 border-slate-700 mb-4">
                    <button 
                      onClick={() => toggleForecastSection('conservative')}
                      className="w-full flex items-center justify-between p-6 hover:bg-slate-700/30 transition-colors"
                    >
                      <div className="flex items-center gap-2">
                        <div className="px-3 py-1 bg-amber-600 rounded-lg">
                          <p className="text-white text-sm font-semibold">
                            Conservative ({performanceData.conservativeRate}%)
                          </p>
                        </div>
                      </div>
                      {expandedSections.conservative ? (
                        <ChevronUp className="w-5 h-5 text-slate-400" />
                      ) : (
                        <ChevronDown className="w-5 h-5 text-slate-400" />
                      )}
                    </button>
                    
                    {expandedSections.conservative && (
                      <div className="px-6 pb-6">
                        {loadingSections.conservative ? (
                          <div className="flex justify-center py-12">
                            <div className="text-center">
                              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-500 mx-auto mb-2"></div>
                              <p className="text-slate-400 text-sm">Loading conservative forecast...</p>
                            </div>
                          </div>
                        ) : forecastsData.conservative ? (
                          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                            {Object.values(forecastsData.conservative.forecasts).map((forecast) => (
                              <div key={forecast.months} className="text-center">
                                <p className="text-slate-400 text-xs mb-2">{forecast.months} Months</p>
                                <p className="text-white text-xl font-bold">{formatCurrency(forecast.projectedValue)}</p>
                                <p className="text-emerald-400 text-xs mt-1">+{formatCurrency(forecast.projectedGain)}</p>
                                <p className="text-slate-500 text-xs">({forecast.projectedROI}%)</p>
                              </div>
                            ))}
                          </div>
                        ) : null}
                      </div>
                    )}
                  </Card>

                  {/* Your Rate Forecast - Collapsible & Lazy Loaded */}
                  <Card className="bg-slate-800/30 border-slate-700 mb-4">
                    <button 
                      onClick={() => toggleForecastSection('investor_specific')}
                      className="w-full flex items-center justify-between p-6 hover:bg-slate-700/30 transition-colors"
                    >
                      <div className="flex items-center gap-2">
                        <div className="px-3 py-1 bg-orange-600 rounded-lg">
                          <p className="text-white text-sm font-semibold">
                            Your Rate ({performanceData.investorSpecificAnnualizedReturn}%)
                          </p>
                        </div>
                      </div>
                      {expandedSections.investor_specific ? (
                        <ChevronUp className="w-5 h-5 text-slate-400" />
                      ) : (
                        <ChevronDown className="w-5 h-5 text-slate-400" />
                      )}
                    </button>
                    
                    {expandedSections.investor_specific && (
                      <div className="px-6 pb-6">
                        {loadingSections.investor_specific ? (
                          <div className="flex justify-center py-12">
                            <div className="text-center">
                              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-500 mx-auto mb-2"></div>
                              <p className="text-slate-400 text-sm">Loading your rate forecast...</p>
                            </div>
                          </div>
                        ) : forecastsData.investor_specific ? (
                          <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                            {Object.values(forecastsData.investor_specific.forecasts).map((forecast) => (
                              <div key={forecast.months} className="text-center">
                                <p className="text-slate-400 text-xs mb-2">{forecast.months} Months</p>
                                <p className="text-white text-xl font-bold">{formatCurrency(forecast.projectedValue)}</p>
                                <p className="text-emerald-400 text-xs mt-1">+{formatCurrency(forecast.projectedGain)}</p>
                                <p className="text-slate-500 text-xs">({forecast.projectedROI}%)</p>
                              </div>
                            ))}
                          </div>
                        ) : null}
                      </div>
                    )}
                  </Card>

                  {/* Disclaimer */}
                  <Card className="bg-slate-800/30 border-slate-700 p-4">
                    <p className="text-slate-400 text-xs text-center">
                      ⚠️ <span className="text-amber-400">Disclaimer:</span> These projections are illustrative only and do not constitute guarantees. 
                      Actual returns may vary. Forecasts use the selected rate applied to your total principal (AED) via compounded growth formula: 
                      FV = Principal × (1 + rate)^(months/12).
                    </p>
                  </Card>
                </div>
              </>
            )}

            {loadingPerformance && (
              <Card className="bg-slate-800/30 border-slate-700 p-12 text-center mb-6">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-500 mx-auto mb-4"></div>
                <p className="text-slate-400">Loading performance metrics...</p>
              </Card>
            )}

            {performanceError && (
              <Card className="bg-red-900/20 border-red-700/50 p-6 mb-6">
                <div className="flex items-start gap-3">
                  <AlertCircle className="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" />
                  <div>
                    <p className="text-red-200 text-sm font-medium mb-1">Error Loading Performance Data</p>
                    <p className="text-red-300/80 text-xs">{performanceError}</p>
                  </div>
                </div>
              </Card>
            )}

            {/* Principal Investments Table */}
            <div className="mb-6 sm:mb-8">
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg sm:text-xl font-bold text-white">Principal Investments</h3>
                {investments.length > 5 && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setShowAllInvestments(!showAllInvestments)}
                    className="text-slate-400 hover:text-white"
                  >
                    {showAllInvestments ? (
                      <>
                        <ChevronUp className="w-4 h-4 mr-1" />
                        Show Less
                      </>
                    ) : (
                      <>
                        <ChevronDown className="w-4 h-4 mr-1" />
                        Show All ({investments.length})
                      </>
                    )}
                  </Button>
                )}
              </div>
              <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                        <TableHead className="text-slate-300 font-semibold text-xs sm:text-sm">Date</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-right text-xs sm:text-sm">Amount (AED)</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-xs sm:text-sm hidden sm:table-cell">Description</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {displayedInvestments.length === 0 ? (
                        <TableRow>
                          <TableCell colSpan={3} className="text-center py-8 text-slate-400 text-sm">
                            No principal investments found
                          </TableCell>
                        </TableRow>
                      ) : (
                        displayedInvestments.map((investment) => (
                          <TableRow key={investment.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                            <TableCell className="text-slate-300 text-xs sm:text-sm">{formatDate(investment.date)}</TableCell>
                            <TableCell className="text-right font-mono text-blue-400 font-semibold text-xs sm:text-sm">
                              {formatCurrency(investment.amount)}
                            </TableCell>
                            <TableCell className="text-slate-300 text-xs sm:text-sm hidden sm:table-cell">{investment.description}</TableCell>
                          </TableRow>
                        ))
                      )}
                    </TableBody>
                  </Table>
                </div>
              </div>
              {!showAllInvestments && investments.length > 5 && (
                <p className="text-slate-500 text-xs text-center mt-2">
                  Showing last 5 of {investments.length} investments
                </p>
              )}
            </div>

            {/* Profit Allocations Table */}
            <div>
              <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg sm:text-xl font-bold text-white">Individual Transaction Breakdown</h3>
                {allocations.length > 5 && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setShowAllAllocations(!showAllAllocations)}
                    className="text-slate-400 hover:text-white"
                  >
                    {showAllAllocations ? (
                      <>
                        <ChevronUp className="w-4 h-4 mr-1" />
                        Show Less
                      </>
                    ) : (
                      <>
                        <ChevronDown className="w-4 h-4 mr-1" />
                        Show All ({allocations.length})
                      </>
                    )}
                  </Button>
                )}
              </div>
              <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
                <div className="overflow-x-auto">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                        <TableHead className="text-slate-300 font-semibold text-xs sm:text-sm">#</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-xs sm:text-sm hidden sm:table-cell">Txn Date</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-xs sm:text-sm hidden md:table-cell">End Date</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-center text-xs sm:text-sm hidden lg:table-cell">Weight</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-right text-xs sm:text-sm hidden xl:table-cell">Capital (AED)</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-right text-xs sm:text-sm">Profit (AED)</TableHead>
                        <TableHead className="text-slate-300 font-semibold text-center text-xs sm:text-sm">Status</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {displayedAllocations.length === 0 ? (
                        <TableRow>
                          <TableCell colSpan={7} className="text-center py-8 text-slate-400 text-sm">
                            No profit allocations found. Run "Calculate Allocations" first.
                          </TableCell>
                        </TableRow>
                      ) : (
                        displayedAllocations.map((allocation) => (
                          <TableRow key={allocation.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                            <TableCell className="font-medium text-white text-xs sm:text-sm">{allocation.transaction_number}</TableCell>
                            <TableCell className="text-slate-300 text-xs sm:text-sm hidden sm:table-cell">{formatDate(allocation.transaction_date)}</TableCell>
                            <TableCell className="text-slate-300 text-xs sm:text-sm hidden md:table-cell">{formatDate(allocation.deal_end_date)}</TableCell>
                            <TableCell className="text-center text-amber-400 font-semibold text-xs sm:text-sm hidden lg:table-cell">
                              {formatPercentage(allocation.weightage * 100)}
                            </TableCell>
                            <TableCell className="text-right font-mono text-purple-400 text-xs sm:text-sm hidden xl:table-cell">
                              {formatCurrency(allocation.invested_capital)}
                            </TableCell>
                            <TableCell className="text-right font-mono text-emerald-400 font-semibold text-xs sm:text-sm">
                              {formatCurrency(allocation.individual_profit)}
                            </TableCell>
                            <TableCell className="text-center">
                              {allocation.deal_status === "Ended" ? (
                                <Badge className="bg-green-500/20 text-green-400 border-green-500/50 text-xs">Ended</Badge>
                              ) : allocation.deal_status === "Ongoing" ? (
                                <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50 text-xs">Ongoing</Badge>
                              ) : (
                                <Badge className="bg-gray-500/20 text-gray-400 border-gray-500/50 text-xs">Not Disbursed</Badge>
                              )}
                            </TableCell>
                          </TableRow>
                        ))
                      )}
                    </TableBody>
                  </Table>
                </div>
              </div>
              {!showAllAllocations && allocations.length > 5 && (
                <p className="text-slate-500 text-xs text-center mt-2">
                  Showing first 5 of {allocations.length} transactions
                </p>
              )}
            </div>
          </>
        )}
      </div>
    </div>
  );
}