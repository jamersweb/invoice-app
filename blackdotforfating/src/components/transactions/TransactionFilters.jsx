import React from 'react';
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Search, X } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function TransactionFilters({ transactions, selectedCustomer, setSelectedCustomer, selectedStatus, setSelectedStatus, searchTerm, setSearchTerm }) {
  const uniqueCustomers = [...new Set(transactions.map(t => t.customer))].sort();

  return (
    <div className="flex flex-col md:flex-row gap-4 mb-6">
      <div className="relative flex-1">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
        <Input
          placeholder="Search transactions..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="pl-10 bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-400"
        />
      </div>
      
      <div className="flex gap-2 flex-wrap">
        <Select value={selectedCustomer} onValueChange={setSelectedCustomer}>
          <SelectTrigger className="w-48 bg-slate-800/50 border-slate-700 text-white">
            <SelectValue placeholder="All Customers" />
          </SelectTrigger>
          <SelectContent className="bg-slate-800 border-slate-700">
            <SelectItem value="all" className="text-white">All Customers</SelectItem>
            {uniqueCustomers.map((customer) => (
              <SelectItem key={customer} value={customer} className="text-white">
                {customer}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        
        <Select value={selectedStatus} onValueChange={setSelectedStatus}>
          <SelectTrigger className="w-40 bg-slate-800/50 border-slate-700 text-white">
            <SelectValue placeholder="All Status" />
          </SelectTrigger>
          <SelectContent className="bg-slate-800 border-slate-700">
            <SelectItem value="all" className="text-white">All Status</SelectItem>
            <SelectItem value="Ongoing" className="text-white">Ongoing</SelectItem>
            <SelectItem value="Ended" className="text-white">Ended</SelectItem>
            <SelectItem value="Not Disbursed" className="text-white">Not Disbursed</SelectItem>
          </SelectContent>
        </Select>
        
        {(selectedCustomer !== "all" || selectedStatus !== "all" || searchTerm) && (
          <Button
            variant="outline"
            size="icon"
            onClick={() => {
              setSelectedCustomer("all");
              setSelectedStatus("all");
              setSearchTerm("");
            }}
            className="bg-slate-800/50 border-slate-700 text-white hover:bg-slate-700"
          >
            <X className="w-4 h-4" />
          </Button>
        )}
      </div>
    </div>
  );
}