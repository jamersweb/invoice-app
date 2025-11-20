
import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Plus, FileSpreadsheet, Download } from "lucide-react";

import TransactionStatsCards from "../components/transactions/TransactionStatsCards";
import TransactionFilters from "../components/transactions/TransactionFilters";
import TransactionTable from "../components/transactions/TransactionTable";
import TransactionForm from "../components/transactions/TransactionForm";

export default function TransactionsPage() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingTransaction, setEditingTransaction] = useState(null);
  const [selectedCustomer, setSelectedCustomer] = useState("all");
  const [selectedStatus, setSelectedStatus] = useState("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [isDownloading, setIsDownloading] = useState(false);

  const queryClient = useQueryClient();

  const { data: transactions, isLoading } = useQuery({
    queryKey: ['transactions'],
    queryFn: () => base44.entities.Transaction.list('-transaction_number'),
    initialData: [],
  });

  const createMutation = useMutation({
    mutationFn: async (transactionData) => {
      const user = await base44.auth.me();
      const newTransaction = await base44.entities.Transaction.create(transactionData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Transaction",
        entity_id: newTransaction.id,
        action: "created",
        user_email: user.email,
        description: `Created transaction #${transactionData.transaction_number} for ${transactionData.customer}`,
        new_data: JSON.stringify(transactionData)
      });
      
      return newTransaction;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] });
      setIsFormOpen(false);
      setEditingTransaction(null);
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, transactionData }) => {
      const user = await base44.auth.me();
      const oldTransaction = transactions.find(t => t.id === id);
      
      const updated = await base44.entities.Transaction.update(id, transactionData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Transaction",
        entity_id: id,
        action: "updated",
        user_email: user.email,
        description: `Updated transaction #${transactionData.transaction_number} for ${transactionData.customer}`,
        old_data: JSON.stringify(oldTransaction),
        new_data: JSON.stringify(transactionData)
      });
      
      return updated;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] });
      setIsFormOpen(false);
      setEditingTransaction(null);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id) => {
      const user = await base44.auth.me();
      const transaction = transactions.find(t => t.id === id);
      
      await base44.entities.Transaction.delete(id);
      
      await base44.entities.AuditLog.create({
        entity_name: "Transaction",
        entity_id: id,
        action: "deleted",
        user_email: user.email,
        description: `Deleted transaction #${transaction.transaction_number} for ${transaction.customer}`,
        old_data: JSON.stringify(transaction)
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['transactions'] });
    },
  });

  const handleSubmit = (transactionData) => {
    if (editingTransaction) {
      updateMutation.mutate({ id: editingTransaction.id, transactionData });
    } else {
      createMutation.mutate(transactionData);
    }
  };

  const handleEdit = (transaction) => {
    setEditingTransaction(transaction);
    setIsFormOpen(true);
  };

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this transaction?')) {
      deleteMutation.mutate(id);
    }
  };

  const handleCancel = () => {
    setIsFormOpen(false);
    setEditingTransaction(null);
  };

  const handleDownloadExcel = async () => {
    setIsDownloading(true);
    try {
      const response = await base44.functions.invoke('exportToExcel', { entityName: 'Transaction' });
      
      // Decode base64 string to binary
      const binaryString = atob(response.data.data);
      const bytes = new Uint8Array(binaryString.length);
      for (let i = 0; i < binaryString.length; i++) {
        bytes[i] = binaryString.charCodeAt(i);
      }
      
      const blob = new Blob([bytes], { 
        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' 
      });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = response.data.filename || 'transactions.xlsx';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      a.remove();
    } catch (error) {
      console.error('Download error:', error);
      alert('Error downloading Excel file. Please try again.');
    } finally {
      setIsDownloading(false);
    }
  };

  const filteredTransactions = transactions.filter(t => {
    const matchesCustomer = selectedCustomer === "all" || t.customer === selectedCustomer;
    const matchesStatus = selectedStatus === "all" || t.status === selectedStatus;
    const matchesSearch = 
      t.customer.toLowerCase().includes(searchTerm.toLowerCase()) ||
      t.transaction_number.toString().includes(searchTerm);
    return matchesCustomer && matchesStatus && matchesSearch;
  });

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-emerald-600/20 rounded-xl">
              <FileSpreadsheet className="w-8 h-8 text-emerald-400" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Master Transactions</h2>
              <p className="text-slate-400 mt-1">Forfaiting Deal Management & Tracking</p>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <TransactionStatsCards transactions={transactions} filteredTransactions={filteredTransactions} />

        {/* Action Buttons */}
        {!isFormOpen && (
          <div className="mb-6 flex justify-end gap-3">
            <Button
              onClick={handleDownloadExcel}
              disabled={isDownloading}
              variant="outline"
              className="border-emerald-600 text-emerald-400 hover:bg-emerald-600/10"
            >
              {isDownloading ? (
                <>
                  <Download className="w-4 h-4 mr-2 animate-bounce" />
                  Downloading...
                </>
              ) : (
                <>
                  <Download className="w-4 h-4 mr-2" />
                  Download Excel
                </>
              )}
            </Button>
            <Button
              onClick={() => setIsFormOpen(true)}
              className="bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/20"
            >
              <Plus className="w-4 h-4 mr-2" />
              Add New Transaction
            </Button>
          </div>
        )}

        {/* Form */}
        <TransactionForm
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          isOpen={isFormOpen}
          editingTransaction={editingTransaction}
        />

        {/* Filters */}
        <TransactionFilters
          transactions={transactions}
          selectedCustomer={selectedCustomer}
          setSelectedCustomer={setSelectedCustomer}
          selectedStatus={selectedStatus}
          setSelectedStatus={setSelectedStatus}
          searchTerm={searchTerm}
          setSearchTerm={setSearchTerm}
        />

        {/* Transaction Table */}
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-500"></div>
          </div>
        ) : (
          <TransactionTable
            transactions={filteredTransactions}
            onDelete={handleDelete}
            onEdit={handleEdit}
          />
        )}
      </div>
    </div>
  );
}
