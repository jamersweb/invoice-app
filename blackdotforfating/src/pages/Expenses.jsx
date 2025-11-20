
import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Plus, Receipt, Download } from "lucide-react";

import ExpenseStatsCards from "../components/expenses/ExpenseStatsCards";
import ExpenseFilters from "../components/expenses/ExpenseFilters";
import ExpenseTable from "../components/expenses/ExpenseTable";
import ExpenseForm from "../components/expenses/ExpenseForm";

export default function ExpensesPage() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingExpense, setEditingExpense] = useState(null);
  const [selectedCategory, setSelectedCategory] = useState("all");
  const [selectedStatus, setSelectedStatus] = useState("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [isDownloading, setIsDownloading] = useState(false);

  const queryClient = useQueryClient();

  const { data: expenses, isLoading } = useQuery({
    queryKey: ['expenses'],
    queryFn: () => base44.entities.Expense.list('-date'),
    initialData: [],
  });

  const createMutation = useMutation({
    mutationFn: async (expenseData) => {
      const user = await base44.auth.me();
      const newExpense = await base44.entities.Expense.create(expenseData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Expense",
        entity_id: newExpense.id,
        action: "created",
        user_email: user.email,
        description: `Created expense: ${expenseData.description} - ${expenseData.amount} AED to ${expenseData.payment_to}`,
        new_data: JSON.stringify(expenseData)
      });
      
      return newExpense;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] });
      setIsFormOpen(false);
      setEditingExpense(null);
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, expenseData }) => {
      const user = await base44.auth.me();
      const oldExpense = expenses.find(e => e.id === id);
      
      const updated = await base44.entities.Expense.update(id, expenseData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Expense",
        entity_id: id,
        action: "updated",
        user_email: user.email,
        description: `Updated expense: ${expenseData.description} - ${expenseData.amount} AED`,
        old_data: JSON.stringify(oldExpense),
        new_data: JSON.stringify(expenseData)
      });
      
      return updated;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] });
      setIsFormOpen(false);
      setEditingExpense(null);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id) => {
      const user = await base44.auth.me();
      const expense = expenses.find(e => e.id === id);
      
      await base44.entities.AuditLog.create({
        entity_name: "Expense",
        entity_id: id,
        action: "deleted",
        user_email: user.email,
        description: `Deleted expense: ${expense.description} - ${expense.amount} AED`,
        old_data: JSON.stringify(expense)
      });

      await base44.entities.Expense.delete(id);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['expenses'] });
    },
  });

  const handleSubmit = (expenseData) => {
    if (editingExpense) {
      updateMutation.mutate({ id: editingExpense.id, expenseData });
    } else {
      createMutation.mutate(expenseData);
    }
  };

  const handleEdit = (expense) => {
    setEditingExpense(expense);
    setIsFormOpen(true);
  };

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this expense?')) {
      deleteMutation.mutate(id);
    }
  };

  const handleCancel = () => {
    setIsFormOpen(false);
    setEditingExpense(null);
  };

  const handleDownloadExcel = async () => {
    setIsDownloading(true);
    try {
      const response = await base44.functions.invoke('exportToExcel', { entityName: 'Expense' });
      
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
      a.download = response.data.filename || 'expenses.xlsx';
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

  const filteredExpenses = expenses.filter(e => {
    const matchesCategory = selectedCategory === "all" || e.expense_category === selectedCategory;
    const matchesStatus = selectedStatus === "all" || e.status === selectedStatus;
    const matchesSearch = 
      e.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
      e.payment_to.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesCategory && matchesStatus && matchesSearch;
  });

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-red-600/20 rounded-xl">
              <Receipt className="w-8 h-8 text-red-400" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Expenses & Payables</h2>
              <p className="text-slate-400 mt-1">Track pending and paid expenses affecting available balance</p>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <ExpenseStatsCards expenses={expenses} filteredExpenses={filteredExpenses} />

        {/* Action Buttons */}
        {!isFormOpen && (
          <div className="mb-6 flex justify-end gap-3">
            <Button
              onClick={handleDownloadExcel}
              disabled={isDownloading}
              variant="outline"
              className="border-red-600 text-red-400 hover:bg-red-600/10"
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
              className="bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-600/20"
            >
              <Plus className="w-4 h-4 mr-2" />
              Add New Expense
            </Button>
          </div>
        )}

        {/* Form */}
        <ExpenseForm
          onSubmit={handleSubmit}
          onCancel={handleCancel}
          isOpen={isFormOpen}
          editingExpense={editingExpense}
        />

        {/* Filters */}
        <ExpenseFilters
          expenses={expenses}
          selectedCategory={selectedCategory}
          setSelectedCategory={setSelectedCategory}
          selectedStatus={selectedStatus}
          setSelectedStatus={setSelectedStatus}
          searchTerm={searchTerm}
          setSearchTerm={setSearchTerm}
        />

        {/* Expense Table */}
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-red-500"></div>
          </div>
        ) : (
          <ExpenseTable
            expenses={filteredExpenses}
            onDelete={handleDelete}
            onEdit={handleEdit}
          />
        )}
      </div>
    </div>
  );
}
