
import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { FileText, Search, X, User, Clock } from "lucide-react";
import { format } from "date-fns";
import { Button } from "@/components/ui/button";

export default function AuditLogPage() {
  const [selectedEntity, setSelectedEntity] = useState("all");
  const [selectedAction, setSelectedAction] = useState("all");
  const [searchTerm, setSearchTerm] = useState("");

  const { data: auditLogs, isLoading } = useQuery({
    queryKey: ['auditLogs'],
    queryFn: () => base44.entities.AuditLog.list('-created_date'),
    initialData: [],
  });

  const formatDateTime = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy HH:mm:ss');
  };

  const getActionBadge = (action) => {
    const badges = {
      created: <Badge className="bg-green-500/20 text-green-400 border-green-500/50">Created</Badge>,
      updated: <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">Updated</Badge>,
      deleted: <Badge className="bg-red-500/20 text-red-400 border-red-500/50">Deleted</Badge>
    };
    return badges[action] || <Badge>{action}</Badge>;
  };

  const getEntityBadge = (entityName) => {
    const colors = {
      Investment: "bg-blue-500/20 text-blue-400 border-blue-500/50",
      Transaction: "bg-emerald-500/20 text-emerald-400 border-emerald-500/50",
      Expense: "bg-red-500/20 text-red-400 border-red-500/50",
      ProfitAllocation: "bg-purple-500/20 text-purple-400 border-purple-500/50"
    };
    return <Badge className={colors[entityName] || "bg-slate-500/20 text-slate-400 border-slate-500/50"}>{entityName}</Badge>;
  };

  const filteredLogs = auditLogs.filter(log => {
    const matchesEntity = selectedEntity === "all" || log.entity_name === selectedEntity;
    const matchesAction = selectedAction === "all" || log.action === selectedAction;
    const matchesSearch = 
      log.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
      log.user_email.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesEntity && matchesAction && matchesSearch;
  });

  const uniqueEntities = [...new Set(auditLogs.map(log => log.entity_name))];

  // Stats
  const totalChanges = auditLogs.length;
  const uniqueUsers = [...new Set(auditLogs.map(log => log.user_email))].length;
  const recentChanges = auditLogs.filter(log => {
    const logDate = new Date(log.created_date);
    const dayAgo = new Date();
    dayAgo.setDate(dayAgo.getDate() - 1);
    return logDate >= dayAgo;
  }).length;

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-indigo-600/20 rounded-xl">
              <FileText className="w-8 h-8 text-indigo-400" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Audit Log</h2>
              <p className="text-slate-400 mt-1">Track all changes, amendments, and deletions across the system</p>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <Card className="bg-gradient-to-br from-indigo-800 to-indigo-900 border-indigo-700 p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-indigo-200 text-sm font-medium mb-1">Total Changes</p>
                <p className="text-white text-3xl font-bold">{totalChanges}</p>
                <p className="text-indigo-200 text-xs mt-1">All-time audit entries</p>
              </div>
              <div className="p-3 bg-indigo-500/20 rounded-xl">
                <FileText className="w-6 h-6 text-indigo-400" />
              </div>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-purple-800 to-purple-900 border-purple-700 p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-purple-200 text-sm font-medium mb-1">Active Users</p>
                <p className="text-white text-3xl font-bold">{uniqueUsers}</p>
                <p className="text-purple-200 text-xs mt-1">Users making changes</p>
              </div>
              <div className="p-3 bg-purple-500/20 rounded-xl">
                <User className="w-6 h-6 text-purple-400" />
              </div>
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-blue-800 to-blue-900 border-blue-700 p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-blue-200 text-sm font-medium mb-1">Recent Changes</p>
                <p className="text-white text-3xl font-bold">{recentChanges}</p>
                <p className="text-blue-200 text-xs mt-1">Last 24 hours</p>
              </div>
              <div className="p-3 bg-blue-500/20 rounded-xl">
                <Clock className="w-6 h-6 text-blue-400" />
              </div>
            </div>
          </Card>
        </div>

        {/* Filters */}
        <div className="flex flex-col md:flex-row gap-4 mb-6">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
            <Input
              placeholder="Search by description or user..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-400"
            />
          </div>
          
          <div className="flex gap-2 flex-wrap">
            <Select value={selectedEntity} onValueChange={setSelectedEntity}>
              <SelectTrigger className="w-48 bg-slate-800/50 border-slate-700 text-white">
                <SelectValue placeholder="All Entities" />
              </SelectTrigger>
              <SelectContent className="bg-slate-800 border-slate-700">
                <SelectItem value="all" className="text-white">All Entities</SelectItem>
                {uniqueEntities.map((entity) => (
                  <SelectItem key={entity} value={entity} className="text-white">
                    {entity}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            
            <Select value={selectedAction} onValueChange={setSelectedAction}>
              <SelectTrigger className="w-40 bg-slate-800/50 border-slate-700 text-white">
                <SelectValue placeholder="All Actions" />
              </SelectTrigger>
              <SelectContent className="bg-slate-800 border-slate-700">
                <SelectItem value="all" className="text-white">All Actions</SelectItem>
                <SelectItem value="created" className="text-white">Created</SelectItem>
                <SelectItem value="updated" className="text-white">Updated</SelectItem>
                <SelectItem value="deleted" className="text-white">Deleted</SelectItem>
              </SelectContent>
            </Select>
            
            {(selectedEntity !== "all" || selectedAction !== "all" || searchTerm) && (
              <Button
                variant="outline"
                size="icon"
                onClick={() => {
                  setSelectedEntity("all");
                  setSelectedAction("all");
                  setSearchTerm("");
                }}
                className="bg-slate-800/50 border-slate-700 text-white hover:bg-slate-700"
              >
                <X className="w-4 h-4" />
              </Button>
            )}
          </div>
        </div>

        {/* Audit Log Table */}
        <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                    <TableHead className="text-slate-300 font-semibold">Timestamp</TableHead>
                    <TableHead className="text-slate-300 font-semibold">User</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Entity</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Action</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Description</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Entity ID</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredLogs.length === 0 ? (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-12 text-slate-400">
                        No audit logs found. Changes will appear here once users start making modifications.
                      </TableCell>
                    </TableRow>
                  ) : (
                    filteredLogs.map((log) => (
                      <TableRow key={log.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                        <TableCell className="text-slate-300 font-mono text-sm">
                          {formatDateTime(log.created_date)}
                        </TableCell>
                        <TableCell className="text-white">
                          <div className="flex items-center gap-2">
                            <div className="p-1 bg-blue-500/20 rounded">
                              <User className="w-3 h-3 text-blue-400" />
                            </div>
                            {log.user_email}
                          </div>
                        </TableCell>
                        <TableCell>{getEntityBadge(log.entity_name)}</TableCell>
                        <TableCell>{getActionBadge(log.action)}</TableCell>
                        <TableCell className="text-slate-300 max-w-md">
                          {log.description}
                        </TableCell>
                        <TableCell className="text-slate-400 font-mono text-xs">
                          {log.entity_id.substring(0, 8)}...
                        </TableCell>
                      </TableRow>
                    ))
                  )}
                </TableBody>
              </Table>
            </div>
          </div>
      </div>
    </div>
  );
}
