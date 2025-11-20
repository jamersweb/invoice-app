import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Trash2, Edit } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { format, addDays, differenceInDays } from "date-fns";
import { motion, AnimatePresence } from "framer-motion";

export default function TransactionTable({ transactions, onDelete, onEdit }) {
  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  const formatDate = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy');
  };

  const calculateValues = (transaction) => {
    const grossSales = transaction.net_amount + (transaction.net_amount * transaction.profit_margin / 100);
    const profit = grossSales - transaction.net_amount;
    const perDayProfit = profit / transaction.sales_cycle;
    const estimatedMonthly = perDayProfit * 30;
    const expectedEndDate = addDays(new Date(transaction.date_of_transaction), transaction.sales_cycle);
    const daysRemaining = differenceInDays(expectedEndDate, new Date());
    const netProfit = profit - (transaction.disbursement_charges || 0);
    const transactionSerial = transaction.customer.substring(0, 3).toUpperCase() + "-" + transaction.transaction_number;

    return {
      grossSales,
      profit,
      perDayProfit,
      estimatedMonthly,
      expectedEndDate,
      daysRemaining,
      netProfit,
      transactionSerial
    };
  };

  const getStatusBadge = (status, daysRemaining) => {
    if (status === "Ended") {
      return <Badge className="bg-green-500/20 text-green-400 border-green-500/50">Ended</Badge>;
    } else if (status === "Not Disbursed") {
      return <Badge className="bg-gray-500/20 text-gray-400 border-gray-500/50">Not Disbursed</Badge>;
    } else {
      return <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">Ongoing</Badge>;
    }
  };

  return (
    <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
              <TableHead className="text-slate-300 font-semibold">#</TableHead>
              <TableHead className="text-slate-300 font-semibold">Serial</TableHead>
              <TableHead className="text-slate-300 font-semibold">Date</TableHead>
              <TableHead className="text-slate-300 font-semibold">Customer</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Net Amount</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Gross Sales</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Profit</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Margin %</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Cycle</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Per Day</TableHead>
              <TableHead className="text-slate-300 font-semibold">End Date</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Days Left</TableHead>
              <TableHead className="text-slate-300 font-semibold text-right">Net Profit</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Status</TableHead>
              <TableHead className="text-slate-300 font-semibold">From</TableHead>
              <TableHead className="text-slate-300 font-semibold text-center">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            <AnimatePresence mode="popLayout">
              {transactions.length === 0 ? (
                <TableRow>
                  <TableCell colSpan={16} className="text-center py-12 text-slate-400">
                    No transactions found. Add your first transaction to get started.
                  </TableCell>
                </TableRow>
              ) : (
                transactions.map((transaction) => {
                  const calc = calculateValues(transaction);
                  return (
                    <motion.tr
                      key={transaction.id}
                      initial={{ opacity: 0, y: 20 }}
                      animate={{ opacity: 1, y: 0 }}
                      exit={{ opacity: 0, x: -100 }}
                      transition={{ duration: 0.2 }}
                      className="border-slate-700/50 hover:bg-slate-800/30 transition-colors"
                    >
                      <TableCell className="font-medium text-white">{transaction.transaction_number}</TableCell>
                      <TableCell className="text-slate-300 font-mono text-xs">{calc.transactionSerial}</TableCell>
                      <TableCell className="text-slate-300">{formatDate(transaction.date_of_transaction)}</TableCell>
                      <TableCell className="text-white font-medium">{transaction.customer}</TableCell>
                      <TableCell className="text-right font-mono text-blue-400">
                        {formatCurrency(transaction.net_amount)}
                      </TableCell>
                      <TableCell className="text-right font-mono text-purple-400">
                        {formatCurrency(calc.grossSales)}
                      </TableCell>
                      <TableCell className="text-right font-mono text-emerald-400">
                        {formatCurrency(calc.profit)}
                      </TableCell>
                      <TableCell className="text-center text-amber-400">
                        {transaction.profit_margin.toFixed(2)}%
                      </TableCell>
                      <TableCell className="text-center text-slate-300">
                        {transaction.sales_cycle}d
                      </TableCell>
                      <TableCell className="text-right font-mono text-xs text-slate-400">
                        {formatCurrency(calc.perDayProfit)}
                      </TableCell>
                      <TableCell className="text-slate-300">
                        {formatDate(calc.expectedEndDate)}
                      </TableCell>
                      <TableCell className="text-center">
                        {transaction.status === "Ended" ? (
                          <span className="text-green-400 font-medium">✓</span>
                        ) : (
                          <span className={calc.daysRemaining < 7 ? "text-red-400 font-medium" : "text-slate-300"}>
                            {calc.daysRemaining}
                          </span>
                        )}
                      </TableCell>
                      <TableCell className="text-right font-mono text-emerald-400 font-semibold">
                        {formatCurrency(calc.netProfit)}
                      </TableCell>
                      <TableCell className="text-center">
                        {getStatusBadge(transaction.status, calc.daysRemaining)}
                      </TableCell>
                      <TableCell>
                        <Badge variant="outline" className="bg-slate-700/50 text-slate-300">
                          {transaction.disbursed_from}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-center">
                        <div className="flex gap-1 justify-center">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onEdit(transaction)}
                            className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10 h-8 w-8"
                          >
                            <Edit className="w-3 h-3" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => onDelete(transaction.id)}
                            className="text-red-400 hover:text-red-300 hover:bg-red-500/10 h-8 w-8"
                          >
                            <Trash2 className="w-3 h-3" />
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