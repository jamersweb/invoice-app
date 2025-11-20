import React from 'react';
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Search, X } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function ExpenseFilters({ expenses, selectedCategory, setSelectedCategory, selectedStatus, setSelectedStatus, searchTerm, setSearchTerm }) {
  return (
    <div className="flex flex-col md:flex-row gap-4 mb-6">
      <div className="relative flex-1">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
        <Input
          placeholder="Search expenses..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="pl-10 bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-400"
        />
      </div>
      
      <div className="flex gap-2 flex-wrap">
        <Select value={selectedCategory} onValueChange={setSelectedCategory}>
          <SelectTrigger className="w-48 bg-slate-800/50 border-slate-700 text-white">
            <SelectValue placeholder="All Categories" />
          </SelectTrigger>
          <SelectContent className="bg-slate-800 border-slate-700">
            <SelectItem value="all" className="text-white">All Categories</SelectItem>
            <SelectItem value="Pending Payment" className="text-white">Pending Payment</SelectItem>
            <SelectItem value="IT Fees" className="text-white">IT Fees</SelectItem>
            <SelectItem value="Transaction Fees" className="text-white">Transaction Fees</SelectItem>
            <SelectItem value="Bank Charges" className="text-white">Bank Charges</SelectItem>
            <SelectItem value="Administrative" className="text-white">Administrative</SelectItem>
            <SelectItem value="Adjustment" className="text-white">Adjustment</SelectItem>
            <SelectItem value="Other" className="text-white">Other</SelectItem>
          </SelectContent>
        </Select>
        
        <Select value={selectedStatus} onValueChange={setSelectedStatus}>
          <SelectTrigger className="w-40 bg-slate-800/50 border-slate-700 text-white">
            <SelectValue placeholder="All Status" />
          </SelectTrigger>
          <SelectContent className="bg-slate-800 border-slate-700">
            <SelectItem value="all" className="text-white">All Status</SelectItem>
            <SelectItem value="Pending" className="text-white">Pending</SelectItem>
            <SelectItem value="Paid" className="text-white">Paid</SelectItem>
          </SelectContent>
        </Select>
        
        {(selectedCategory !== "all" || selectedStatus !== "all" || searchTerm) && (
          <Button
            variant="outline"
            size="icon"
            onClick={() => {
              setSelectedCategory("all");
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