import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Trash2, Eye } from "lucide-react";
import { format } from "date-fns";
import { motion, AnimatePresence } from "framer-motion";
import { Link } from "react-router-dom";
import { createPageUrl } from "@/utils";
import { useQuery } from '@tanstack/react-query';
import { base44 } from "@/api/base44Client";

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

export default function InvestmentTable({ investments, onDelete }) {
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const formatDate = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy');
  };

  // Fetch users to map investor names to IDs
  const { data: users } = useQuery({
    queryKey: ['users'],
    queryFn: () => base44.entities.User.list(),
    initialData: [],
    staleTime: 5 * 60 * 1000,
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
    <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
              <TableHead className="text-slate-300 font-semibold">Name / Entity</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Amount (AED)</TableHead>
              <TableHead className="text-slate-300 font-semibold">Date</TableHead>
              <TableHead className="text-slate-300 font-semibold">Description</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <AnimatePresence mode="popLayout">
              {investments.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={5} className="text-center py-12 text-slate-400">
                    No investments found. Add your first investment to get started.
                  </TableCell>
                </TableRow>
              ) : (
                investments.map((investment) => {
                  const investorId = getInvestorId(investment.name);
                  
                  return (
                    <motion.tr
                      key={investment.id}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, x: -100 }}
                      transition={{ duration: 0.2 }}
                      className="border-slate-700/50 hover:bg-slate-800/30 transition-colors"
                    >
                      <TableCell className="font-medium text-white">{investment.name}</TableCell>
                      <TableCell className="text-right font-mono text-blue-400">
                        {formatCurrency(investment.amount)}
                      </TableCell>
                      <TableCell className="text-slate-300">{formatDate(investment.date)}</TableCell>
                      <TableCell className="text-slate-300">{investment.description}</TableCell>
                      <TableCell className="text-center">
                        <div className="flex gap-1 justify-center">
                          <Link to={createPageUrl('InvestorDashboard') + '?id=' + investorId}>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10"
                              title={`View dashboard for ${investment.name}`}
                            >
                              <Eye className="w-4 h-4" />
                            </Button>
                          </Link>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onDelete(investment.id)}
                            className="text-red-400 hover:text-red-300 hover:bg-red-500/10"
                            title={`Delete investment by ${investment.name}`}
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </motion.tr>
                  );
                })
              )}
            </AnimatePresence>
          </TableBody>
        </Table>
      </div>
    </div>
  );
}