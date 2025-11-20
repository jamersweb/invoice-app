import React from "react";
import { Card } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { AlertTriangle, AlertCircle, Info } from "lucide-react";

export default function AlertsDashboard({ alerts }) {
  const criticalAlerts = alerts.filter(a => a.severity === 'critical');
  const warningAlerts = alerts.filter(a => a.severity === 'warning');
  const infoAlerts = alerts.filter(a => a.severity === 'info');
  
  const getSeverityIcon = (severity) => {
    switch(severity) {
      case 'critical': return <AlertTriangle className="w-4 h-4 text-red-400" />;
      case 'warning': return <AlertCircle className="w-4 h-4 text-amber-400" />;
      default: return <Info className="w-4 h-4 text-blue-400" />;
    }
  };
  
  const getSeverityBadge = (severity) => {
    switch(severity) {
      case 'critical': 
        return <Badge className="bg-red-500/20 text-red-400 border-red-500/50 text-xs">Critical</Badge>;
      case 'warning': 
        return <Badge className="bg-amber-500/20 text-amber-400 border-amber-500/50 text-xs">Warning</Badge>;
      default: 
        return <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50 text-xs">Info</Badge>;
    }
  };
  
  if (alerts.length === 0) {
    return (
      <Card className="bg-green-900/20 border-green-700/50 p-6 text-center">
        <div className="text-4xl mb-2">✅</div>
        <p className="text-green-400 font-semibold">All Systems Healthy</p>
        <p className="text-green-300/80 text-xs mt-1">No alerts or warnings at this time</p>
      </Card>
    );
  }
  
  return (
    <div className="space-y-4">
      {/* Summary Stats */}
      <div className="grid grid-cols-3 gap-4">
        <Card className="bg-red-900/20 border-red-700/50 p-3 text-center">
          <p className="text-red-200 text-xs mb-1">Critical</p>
          <p className="text-white text-2xl font-bold">{criticalAlerts.length}</p>
        </Card>
        <Card className="bg-amber-900/20 border-amber-700/50 p-3 text-center">
          <p className="text-amber-200 text-xs mb-1">Warning</p>
          <p className="text-white text-2xl font-bold">{warningAlerts.length}</p>
        </Card>
        <Card className="bg-blue-900/20 border-blue-700/50 p-3 text-center">
          <p className="text-blue-200 text-xs mb-1">Info</p>
          <p className="text-white text-2xl font-bold">{infoAlerts.length}</p>
        </Card>
      </div>

      {/* Alert List */}
      <div className="space-y-2">
        {alerts.map((alert, idx) => (
          <Card 
            key={idx} 
            className={`p-4 border ${
              alert.severity === 'critical' ? 'bg-red-900/20 border-red-700/50' :
              alert.severity === 'warning' ? 'bg-amber-900/20 border-amber-700/50' :
              'bg-blue-900/20 border-blue-700/50'
            }`}
          >
            <div className="flex items-start gap-3">
              {getSeverityIcon(alert.severity)}
              <div className="flex-1">
                <div className="flex items-center justify-between mb-1">
                  <p className="text-white font-semibold text-sm">{alert.title}</p>
                  {getSeverityBadge(alert.severity)}
                </div>
                <p className="text-slate-300 text-xs">{alert.message}</p>
                {alert.value && (
                  <p className="text-slate-400 text-xs mt-1">Current: {alert.value}</p>
                )}
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
}