import React from "react";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Shield, TrendingUp, Droplets, Users, Activity } from "lucide-react";

export default function PortfolioHealthScore({ healthScore }) {
  const formatPercent = (value) => value.toFixed(2) + '%';
  
  const getScoreColor = (score) => {
    if (score >= 80) return { bg: 'from-green-600 to-green-500', text: 'text-green-400', label: 'Excellent' };
    if (score >= 60) return { bg: 'from-blue-600 to-blue-500', text: 'text-blue-400', label: 'Good' };
    if (score >= 40) return { bg: 'from-yellow-600 to-yellow-500', text: 'text-yellow-400', label: 'Fair' };
    return { bg: 'from-red-600 to-red-500', text: 'text-red-400', label: 'Needs Attention' };
  };
  
  const overallColor = getScoreColor(healthScore.overall);
  
  return (
    <div>
      {/* Overall Health Score - Large Display */}
      <Card className="bg-slate-800/50 border-slate-700 p-6 mb-6 text-center relative overflow-hidden">
        <div className={`absolute inset-0 bg-gradient-to-br ${overallColor.bg} opacity-10`}></div>
        <div className="relative z-10">
          <p className="text-slate-300 text-sm font-medium mb-2">Portfolio Health Score</p>
          <div className={`text-6xl font-bold ${overallColor.text} mb-2`}>
            {healthScore.overall.toFixed(0)}
          </div>
          <Badge className={`${overallColor.text} bg-slate-800 border-${overallColor.text.split('-')[1]}-500/50 text-sm`}>
            {overallColor.label}
          </Badge>
          <p className="text-slate-400 text-xs mt-3">Composite score across all risk dimensions</p>
        </div>
      </Card>

      {/* Component Breakdown */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <Card className="bg-blue-800/50 border-blue-700 p-4">
          <div className="flex items-center gap-2 mb-2">
            <Droplets className="w-4 h-4 text-blue-400" />
            <p className="text-blue-200 text-xs font-medium">Liquidity</p>
          </div>
          <p className="text-white text-2xl font-bold">{healthScore.liquidity.toFixed(0)}</p>
          <p className="text-blue-300 text-xs mt-1">
            {formatPercent(healthScore.rawMetrics.idlePercent)} idle
          </p>
        </Card>

        <Card className="bg-emerald-800/50 border-emerald-700 p-4">
          <div className="flex items-center gap-2 mb-2">
            <TrendingUp className="w-4 h-4 text-emerald-400" />
            <p className="text-emerald-200 text-xs font-medium">Yield Quality</p>
          </div>
          <p className="text-white text-2xl font-bold">{healthScore.yield.toFixed(0)}</p>
          <p className="text-emerald-300 text-xs mt-1">
            {formatPercent(healthScore.rawMetrics.weightedAPY)} APY
          </p>
        </Card>

        <Card className="bg-orange-800/50 border-orange-700 p-4">
          <div className="flex items-center gap-2 mb-2">
            <Shield className="w-4 h-4 text-orange-400" />
            <p className="text-orange-200 text-xs font-medium">Counterparty Risk</p>
          </div>
          <p className="text-white text-2xl font-bold">{healthScore.counterpartyRisk.toFixed(0)}</p>
          <p className="text-orange-300 text-xs mt-1">
            HHI: {healthScore.rawMetrics.hhi.toFixed(0)}
          </p>
        </Card>

        <Card className="bg-purple-800/50 border-purple-700 p-4">
          <div className="flex items-center gap-2 mb-2">
            <Users className="w-4 h-4 text-purple-400" />
            <p className="text-purple-200 text-xs font-medium">Investor Stability</p>
          </div>
          <p className="text-white text-2xl font-bold">{healthScore.investorStability.toFixed(0)}</p>
          <p className="text-purple-300 text-xs mt-1">
            {healthScore.rawMetrics.investorsAtRisk} at risk
          </p>
        </Card>

        <Card className="bg-cyan-800/50 border-cyan-700 p-4">
          <div className="flex items-center gap-2 mb-2">
            <Activity className="w-4 h-4 text-cyan-400" />
            <p className="text-cyan-200 text-xs font-medium">Deal Velocity</p>
          </div>
          <p className="text-white text-2xl font-bold">{healthScore.dealVelocity.toFixed(0)}</p>
          <p className="text-cyan-300 text-xs mt-1">
            {healthScore.rawMetrics.cyclesPerMonth.toFixed(2)}x/mo
          </p>
        </Card>
      </div>
    </div>
  );
}