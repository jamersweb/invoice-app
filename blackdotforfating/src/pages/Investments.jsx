import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Plus, Building2, Download } from "lucide-react";

import StatsCards from "../components/investments/StatsCards";
import InvestmentFilters from "../components/investments/InvestmentFilters";
import InvestmentTable from "../components/investments/InvestmentTable";
import AddInvestmentForm from "../components/investments/AddInvestmentForm";

export default function InvestmentsPage() {
  const [isAdding, setIsAdding] = useState(false);
  const [selectedInvestor, setSelectedInvestor] = useState("all");
  const [searchTerm, setSearchTerm] = useState("");
  const [isDownloading, setIsDownloading] = useState(false);

  const queryClient = useQueryClient();

  const { data: investments, isLoading } = useQuery({
    queryKey: ['investments'],
    queryFn: () => base44.entities.Investment.list('-date'),
    initialData: [],
  });

  const createMutation = useMutation({
    mutationFn: async (investmentData) => {
      const user = await base44.auth.me();
      const newInvestment = await base44.entities.Investment.create(investmentData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Investment",
        entity_id: newInvestment.id,
        action: "created",
        user_email: user.email,
        description: `Created investment: ${investmentData.name} - ${investmentData.amount} AED`,
        new_data: JSON.stringify(investmentData)
      });
      
      return newInvestment;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['investments'] });
      setIsAdding(false);
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id) => {
      const user = await base44.auth.me();
      const investmentToDelete = investments.find(inv => inv.id === id);
      
      await base44.entities.Investment.delete(id);
      
      if (investmentToDelete) {
        await base44.entities.AuditLog.create({
          entity_name: "Investment",
          entity_id: id,
          action: "deleted",
          user_email: user.email,
          description: `Deleted investment: ${investmentToDelete.name} - ${investmentToDelete.amount} AED`,
          old_data: JSON.stringify(investmentToDelete)
        });
      }
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['investments'] });
    },
  });

  const handleAdd = (investmentData) => {
    createMutation.mutate(investmentData);
  };

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this investment?')) {
      deleteMutation.mutate(id);
    }
  };

  const handleDownloadExcel = async () => {
    setIsDownloading(true);
    try {
      const response = await base44.functions.invoke('exportToExcel', { entityName: 'Investment' });
      
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
      a.download = response.data.filename || 'investments.xlsx';
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

  const filteredInvestments = investments.filter(inv => {
    const matchesInvestor = selectedInvestor === "all" || inv.name === selectedInvestor;
    const matchesSearch = inv.name.toLowerCase().includes(searchTerm.toLowerCase());
    return matchesInvestor && matchesSearch;
  });

  return (
    <div className="p-6">
      <div className="max-w-7xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-blue-600/20 rounded-xl">
              <Building2 className="w-8 h-8 text-blue-400" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Principal Investments</h2>
              <p className="text-slate-400 mt-1">Track investor principal investments and dates</p>
            </div>
          </div>
        </div>

        {/* Stats Cards */}
        <StatsCards investments={investments} filteredInvestments={filteredInvestments} />

        {/* Action Buttons */}
        {!isAdding && (
          <div className="mb-6 flex justify-end gap-3">
            <Button
              onClick={handleDownloadExcel}
              disabled={isDownloading}
              variant="outline"
              className="border-blue-600 text-blue-400 hover:bg-blue-600/10"
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
              onClick={() => setIsAdding(true)}
              className="bg-blue-600 hover:bg-blue-700 text-white shadow-lg shadow-blue-600/20"
            >
              <Plus className="w-4 h-4 mr-2" />
              Add New Investment
            </Button>
          </div>
        )}

        {/* Add Form */}
        <AddInvestmentForm
          onAdd={handleAdd}
          isAdding={isAdding}
          onCancel={() => setIsAdding(false)}
        />

        {/* Filters */}
        <InvestmentFilters
          investments={investments}
          selectedInvestor={selectedInvestor}
          setSelectedInvestor={setSelectedInvestor}
          searchTerm={searchTerm}
          setSearchTerm={setSearchTerm}
        />

        {/* Investment Table */}
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
          </div>
        ) : (
          <InvestmentTable
            investments={filteredInvestments}
            onDelete={handleDelete}
          />
        )}
      </div>
    </div>
  );
}