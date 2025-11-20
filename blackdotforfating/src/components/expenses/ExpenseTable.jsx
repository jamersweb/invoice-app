import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Trash2, Edit } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { format } from "date-fns";
import { motion, AnimatePresence } from "framer-motion";

export default function ExpenseTable({ expenses, onDelete, onEdit }) {
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const formatDate = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy');
  };

  const getStatusBadge = (status) => {
    if (status === "Paid") {
      return <Badge className="bg-green-500/20 text-green-400 border-green-500/50">Paid</Badge>;
    } else {
      return <Badge className="bg-red-500/20 text-red-400 border-red-500/50">Pending</Badge>;
    }
  };

  const getCategoryBadge = (category) => {
    const colors = {
      "Pending Payment": "bg-amber-500/20 text-amber-400 border-amber-500/50",
      "IT Fees": "bg-blue-500/20 text-blue-400 border-blue-500/50",
      "Transaction Fees": "bg-purple-500/20 text-purple-400 border-purple-500/50",
      "Bank Charges": "bg-indigo-500/20 text-indigo-400 border-indigo-500/50",
      "Administrative": "bg-cyan-500/20 text-cyan-400 border-cyan-500/50",
      "Adjustment": "bg-emerald-500/20 text-emerald-400 border-emerald-500/50",
      "Other": "bg-slate-500/20 text-slate-400 border-slate-500/50"
    };
    
    return <Badge className={colors[category] || colors["Other"]}>{category}</Badge>;
  };

  const getAmountDisplay = (amount, category) => {
    const isAdjustment = category === "Adjustment";
    const isNegative = amount < 0;
    const absAmount = Math.abs(amount);
    
    // For adjustments: negative adds to balance (show in green), positive subtracts (show in red)
    // For regular expenses: always show in red (they're costs)
    const colorClass = isAdjustment 
      ? (isNegative ? "text-green-400" : "text-red-400")
      : "text-red-400";
    
    return (
      <span className={`${colorClass} font-semibold`}>
        {isNegative ? '-' : '+'}{formatCurrency(absAmount)}
      </span>
    );
  };

  return (
    <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
              <TableHead className="text-slate-300 font-semibold">Date</TableHead>
              <TableHead className="text-slate-300 font-semibold">Description</TableHead>
              <TableHead className="text-slate-300 font-semibold">Payment To</TableHead>
              <TableHead className="text-slate-300 font-semibold">Category</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Amount</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Status</TableHead>
              <TableHead className="text-slate-300 font-semibold">Txn #</TableHead>
              <TableHead className="text-slate-300 font-semibold">Notes</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <AnimatePresence mode="popLayout">
              {expenses.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={9} className="text-center py-12 text-slate-400">
                    No expenses found. Add your first expense to get started.
                  </TableCell>
                </TableRow>
              ) : (
                expenses.map((expense) => (
                  <motion.tr
                    key={expense.id}
                    initial={{ opacity: 0, y: 20 }}
                    animate={{ opacity: 1, y: 0 }}
                    exit={{ opacity: 0, x: -100 }}
                    transition={{ duration: 0.2 }}
                    className="border-slate-700/50 hover:bg-slate-800/30 transition-colors"
                  >
                    <TableCell className="text-slate-300">{formatDate(expense.date)}</TableCell>
                    <TableCell className="text-white font-medium">{expense.description}</TableCell>
                    <TableCell className="text-white">{expense.payment_to}</TableCell>
                    <TableCell>{getCategoryBadge(expense.expense_category)}</TableCell>
                    <TableCell className="text-right font-mono">
                      {getAmountDisplay(expense.amount, expense.expense_category)}
                    </TableCell>
                    <TableCell className="text-center">{getStatusBadge(expense.status)}</TableCell>
                    <TableCell className="text-slate-300">
                      {expense.related_transaction_number || '-'}
                    </TableCell>
                    <TableCell className="text-slate-400 text-sm max-w-xs truncate">
                      {expense.notes || '-'}
                    </TableCell>
                    <TableCell className="text-center">
                      <div className="flex gap-1 justify-center">
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => onEdit(expense)}
                          className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 h-8 w-8"
                        >
                          <Edit className="w-3 h-3" />
                        </Button>
                        <Button
                          variant="ghost"
                          size="icon"
                          onClick={() => onDelete(expense.id)}
                          className="text-red-400 hover:text-red-300 hover:bg-red-500/10 h-8 w-8"
                        >
                          <Trash2 className="w-3 h-3" />
                        </Button>
                      </div>
                    </TableCell>
                  </motion.tr>
                ))
              )}
            </AnimatePresence>
          </TableBody>
        </Table>
      </div>
    </div>
  );
}