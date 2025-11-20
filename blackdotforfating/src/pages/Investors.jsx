
import React from "react";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Link } from "react-router-dom";
import { createPageUrl } from "@/utils";
import { Users, TrendingUp, Wallet, ArrowRight } from "lucide-react";

// Generate consistent hash from string
const generateInvestorHash = (name) => {
  let hash = 0;
  for (let i = 0; i < name.length; i++) {
    const char = name.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32-bit integer
  }
  return Math.abs(hash).toString(36);
};

export default function InvestorsPage() {
  const { data: investments, isLoading: investmentsLoading } = useQuery({
    queryKey: ['investments'],
    queryFn: () => base44.entities.Investment.list(),
    initialData: [],
  });

  const { data: allocations, isLoading: allocationsLoading } = useQuery({
    queryKey: ['profitAllocations'],
    queryFn: () => base44.entities.ProfitAllocation.list(),
    initialData: [],
  });

  const { data: users, isLoading: usersLoading } = useQuery({
    queryKey: ['users'],
    queryFn: () => base44.entities.User.list(),
    initialData: [],
  });

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const isLoading = investmentsLoading || allocationsLoading || usersLoading;

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  // Group data by investor
  const investorSummary = {};
  
  // Calculate principals
  investments.forEach(inv => {
    if (!investorSummary[inv.name]) {
      investorSummary[inv.name] = {
        principal: 0,
        realizedProfit: 0,
        expectedProfit: 0,
        totalProfit: 0,
        capital: 0,
        dealsCount: 0
      };
    }
    investorSummary[inv.name].principal += inv.amount;
  });

  // Calculate profits
  allocations.forEach(alloc => {
    if (!investorSummary[alloc.investor_name]) {
      investorSummary[alloc.investor_name] = {
        principal: 0,
        realizedProfit: 0,
        expectedProfit: 0,
        totalProfit: 0,
        capital: 0,
        dealsCount: 0
      };
    }
    
    investorSummary[alloc.investor_name].totalProfit += alloc.individual_profit;
    if (alloc.deal_status === "Ended") {
      investorSummary[alloc.investor_name].realizedProfit += alloc.individual_profit;
    } else if (alloc.deal_status === "Ongoing") {
      investorSummary[alloc.investor_name].expectedProfit += alloc.individual_profit;
    }
    investorSummary[alloc.investor_name].dealsCount += 1;
  });

  // Calculate capital for each investor
  Object.keys(investorSummary).forEach(investor => {
    investorSummary[investor].capital = investorSummary[investor].principal + investorSummary[investor].realizedProfit;
  });

  // Helper function to get investor_id from investor name
  const getInvestorId = (investorName) => {
    const user = users.find(u => 
      (u.investor_name === investorName || u.full_name === investorName) && u.investor_id
    );
    // Return investor_id if exists, otherwise return hash of investor name
    return user?.investor_id || generateInvestorHash(investorName);
  };

  return (
    <div className="p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-gradient-to-br from-blue-600 to-purple-600 rounded-xl">
              <Users className="w-8 h-8 text-white" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Investor Dashboards</h2>
              <p className="text-slate-400 mt-1">View detailed portfolio information for each investor</p>
            </div>
          </div>
        </div>

        {Object.keys(investorSummary).length === 0 ? (
          <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
            <Users className="w-16 h-16 text-slate-600 mx-auto mb-4" />
            <p className="text-slate-400 text-lg mb-2">No investors found</p>
            <p className="text-slate-500 text-sm">Add investments to see investor dashboards here</p>
          </Card>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {Object.entries(investorSummary).map(([investor, summary]) => {
              const investorId = getInvestorId(investor);
              const linkUrl = createPageUrl('InvestorDashboard') + '?id=' + investorId;
              
              const hasUserAccount = users.some(u => 
                (u.investor_name === investor || u.full_name === investor) && u.investor_id
              );
              
              const cardContent = (
                <>
                  <div className="absolute top-0 right-0 w-32 h-32 transform translate-x-8 -translate-y-8 bg-blue-500 rounded-full opacity-10 group-hover:opacity-20 transition-opacity" />
                  
                  <div className="p-6">
                    {/* Investor Name Header */}
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center gap-3">
                        <div className="p-2 bg-blue-500/20 rounded-lg">
                          <Wallet className="w-5 h-5 text-blue-400" />
                        </div>
                        <h3 className="text-xl font-bold text-white">{investor}</h3>
                      </div>
                      <ArrowRight className="w-5 h-5 text-slate-400 group-hover:text-blue-400 group-hover:translate-x-1 transition-all" />
                    </div>

                    {/* Stats Grid */}
                    <div className="space-y-3">
                      <div className="flex justify-between items-center p-3 bg-slate-900/50 rounded-lg">
                        <span className="text-slate-400 text-sm">Total Profit</span>
                        <span className="text-emerald-400 font-bold">{formatCurrency(summary.totalProfit)}</span>
                      </div>
                      
                      <div className="grid grid-cols-2 gap-2">
                        <div className="p-2 bg-slate-900/30 rounded-lg">
                          <p className="text-slate-500 text-xs mb-1">Realized</p>
                          <p className="text-green-400 font-semibold text-sm">{formatCurrency(summary.realizedProfit)}</p>
                        </div>
                        <div className="p-2 bg-slate-900/30 rounded-lg">
                          <p className="text-slate-500 text-xs mb-1">Expected</p>
                          <p className="text-amber-400 font-semibold text-sm">{formatCurrency(summary.expectedProfit)}</p>
                        </div>
                      </div>

                      <div className="pt-3 border-t border-slate-700/50 space-y-2">
                        <div className="flex justify-between text-sm">
                          <span className="text-slate-400">Capital Balance (Realized)</span>
                          <span className="text-blue-400 font-semibold">{formatCurrency(summary.capital)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span className="text-slate-400">Principal</span>
                          <span className="text-purple-400 font-semibold">{formatCurrency(summary.principal)}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span className="text-slate-400">Total Deals</span>
                          <span className="text-slate-300 font-semibold">{summary.dealsCount}</span>
                        </div>
                      </div>
                      {!hasUserAccount && (
                        <p className="text-xs text-amber-400 mt-4">ℹ️ No user account - viewing as admin</p>
                      )}
                    </div>
                  </div>
                </>
              );

              return (
                <Link 
                  key={investor} 
                  to={linkUrl}
                  className="block transition-transform hover:scale-105"
                >
                  <Card className="relative overflow-hidden bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700 hover:border-blue-500/50 transition-all duration-300 cursor-pointer group">
                    {cardContent}
                  </Card>
                </Link>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}
