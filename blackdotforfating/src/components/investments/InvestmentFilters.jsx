import React from 'react';
import { Input } from "@/components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Search, X } from "lucide-react";
import { Button } from "@/components/ui/button";

export default function InvestmentFilters({ investments, selectedInvestor, setSelectedInvestor, searchTerm, setSearchTerm }) {
  const uniqueInvestors = [...new Set(investments.map(inv => inv.name))].sort();

  return (
    <div className="flex flex-col md:flex-row gap-4 mb-6">
      <div className="relative flex-1">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
        <Input
          placeholder="Search by investor name..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
          className="pl-10 bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-400"
        />
      </div>
      
      <div className="flex gap-2">
        <Select value={selectedInvestor} onValueChange={setSelectedInvestor}>
          <SelectTrigger className="w-48 bg-slate-800/50 border-slate-700 text-white">
            <SelectValue placeholder="All Investors" />
          </SelectTrigger>
          <SelectContent className="bg-slate-800 border-slate-700">
            <SelectItem value="all" className="text-white">All Investors</SelectItem>
            {uniqueInvestors.map((investor) => (
              <SelectItem key={investor} value={investor} className="text-white">
                {investor}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        
        {(selectedInvestor !== "all" || searchTerm) && (
          <Button
            variant="outline"
            size="icon"
            onClick={() => {
              setSelectedInvestor("all");
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