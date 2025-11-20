
import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Users, Plus, X, Save, Edit, Trash2, Search, Building2, Eye, Download } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Link } from "react-router-dom";
import { createPageUrl } from "@/utils";

export default function CustomersPage() {
  const [isFormOpen, setIsFormOpen] = useState(false);
  const [editingCustomer, setEditingCustomer] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [isDownloading, setIsDownloading] = useState(false);
  const [formData, setFormData] = useState({
    name: "",
    contact_person: "",
    email: "",
    phone: "",
    address: "",
    notes: ""
  });

  const queryClient = useQueryClient();

  const { data: customers, isLoading } = useQuery({
    queryKey: ['customers'],
    queryFn: () => base44.entities.Customer.list('name'),
    initialData: [],
  });

  const { data: transactions } = useQuery({
    queryKey: ['transactions'],
    queryFn: () => base44.entities.Transaction.list(),
    initialData: [],
  });

  const createMutation = useMutation({
    mutationFn: async (customerData) => {
      const user = await base44.auth.me();
      const newCustomer = await base44.entities.Customer.create(customerData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Customer",
        entity_id: newCustomer.id,
        action: "created",
        user_email: user.email,
        description: `Created customer: ${customerData.name}`,
        new_data: JSON.stringify(customerData)
      });
      
      return newCustomer;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      handleCancel();
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, customerData }) => {
      const user = await base44.auth.me();
      const oldCustomer = customers.find(c => c.id === id);
      
      const updated = await base44.entities.Customer.update(id, customerData);
      
      await base44.entities.AuditLog.create({
        entity_name: "Customer",
        entity_id: id,
        action: "updated",
        user_email: user.email,
        description: `Updated customer: ${customerData.name}`,
        old_data: JSON.stringify(oldCustomer),
        new_data: JSON.stringify(customerData)
      });
      
      return updated;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
      handleCancel();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id) => {
      const user = await base44.auth.me();
      const customer = customers.find(c => c.id === id);
      
      await base44.entities.Customer.delete(id);
      
      await base44.entities.AuditLog.create({
        entity_name: "Customer",
        entity_id: id,
        action: "deleted",
        user_email: user.email,
        description: `Deleted customer: ${customer.name}`,
        old_data: JSON.stringify(customer)
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customers'] });
    },
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    if (editingCustomer) {
      updateMutation.mutate({ id: editingCustomer.id, customerData: formData });
    } else {
      createMutation.mutate(formData);
    }
  };

  const handleEdit = (customer) => {
    setEditingCustomer(customer);
    setFormData({
      name: customer.name || "",
      contact_person: customer.contact_person || "",
      email: customer.email || "",
      phone: customer.phone || "",
      address: customer.address || "",
      notes: customer.notes || ""
    });
    setIsFormOpen(true);
  };

  const handleDelete = (id, customerName) => {
    const transactionCount = transactions.filter(t => t.customer === customerName).length;
    
    if (transactionCount > 0) {
      alert(`Cannot delete customer "${customerName}" because there are ${transactionCount} transaction(s) linked to them.`);
      return;
    }
    
    if (confirm(`Are you sure you want to delete customer "${customerName}"?`)) {
      deleteMutation.mutate(id);
    }
  };

  const handleCancel = () => {
    setIsFormOpen(false);
    setEditingCustomer(null);
    setFormData({
      name: "",
      contact_person: "",
      email: "",
      phone: "",
      address: "",
      notes: ""
    });
  };

  const handleDownloadExcel = async () => {
    setIsDownloading(true);
    try {
      const response = await base44.functions.invoke('exportToExcel', { entityName: 'Customer' });
      
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
      a.download = response.data.filename || 'customers.xlsx';
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

  const filteredCustomers = customers.filter(c => 
    c.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (c.contact_person && c.contact_person.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  // Get transaction count per customer
  const getTransactionCount = (customerName) => {
    return transactions.filter(t => t.customer === customerName).length;
  };

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div className="p-3 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl">
                <Building2 className="w-8 h-8 text-white" />
              </div>
              <div>
                <h2 className="text-3xl font-bold text-white">Customers</h2>
                <p className="text-slate-400 mt-1">Manage customer profiles and information</p>
              </div>
            </div>
            {!isFormOpen && (
              <div className="flex gap-3">
                <Button
                  onClick={handleDownloadExcel}
                  disabled={isDownloading}
                  variant="outline"
                  className="border-indigo-600 text-indigo-400 hover:bg-indigo-600/10"
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
                  className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20"
                >
                  <Plus className="w-4 h-4 mr-2" />
                  Add Customer
                </Button>
              </div>
            )}
          </div>
        </div>

        {/* Form */}
        {isFormOpen && (
          <Card className="mb-6 bg-slate-800/50 border-slate-700 backdrop-blur-sm p-6">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-white text-lg font-semibold flex items-center gap-2">
                {editingCustomer ? <Save className="w-5 h-5 text-blue-400" /> : <Plus className="w-5 h-5 text-blue-400" />}
                {editingCustomer ? "Edit Customer" : "Add New Customer"}
              </h3>
              <Button variant="ghost" size="icon" onClick={handleCancel} className="text-slate-400 hover:text-white">
                <X className="w-4 h-4" />
              </Button>
            </div>

            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="name" className="text-slate-300">Customer Name *</Label>
                  <Input
                    id="name"
                    value={formData.name}
                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                    placeholder="e.g., Nissi Trading LLC"
                    required
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="contact_person" className="text-slate-300">Contact Person</Label>
                  <Input
                    id="contact_person"
                    value={formData.contact_person}
                    onChange={(e) => setFormData({ ...formData, contact_person: e.target.value })}
                    placeholder="e.g., John Smith"
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="email" className="text-slate-300">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                    placeholder="e.g., contact@company.com"
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="phone" className="text-slate-300">Phone</Label>
                  <Input
                    id="phone"
                    value={formData.phone}
                    onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                    placeholder="e.g., +971 50 123 4567"
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="address" className="text-slate-300">Address</Label>
                  <Input
                    id="address"
                    value={formData.address}
                    onChange={(e) => setFormData({ ...formData, address: e.target.value })}
                    placeholder="e.g., Dubai, UAE"
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>

                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="notes" className="text-slate-300">Notes</Label>
                  <Textarea
                    id="notes"
                    value={formData.notes}
                    onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                    placeholder="Additional notes about this customer..."
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4">
                <Button type="button" variant="outline" onClick={handleCancel} className="bg-slate-900/50 border-slate-700 text-white hover:bg-slate-800">
                  Cancel
                </Button>
                <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">
                  {editingCustomer ? <><Save className="w-4 h-4 mr-2" />Update Customer</> : <><Plus className="w-4 h-4 mr-2" />Add Customer</>}
                </Button>
              </div>
            </form>
          </Card>
        )}

        {/* Search */}
        <div className="mb-6">
          <div className="relative max-w-md">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-slate-400 w-4 h-4" />
            <Input
              placeholder="Search customers..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="pl-10 bg-slate-800/50 border-slate-700 text-white placeholder:text-slate-400"
            />
          </div>
        </div>

        {/* Customers Table */}
        {isLoading ? (
          <div className="flex justify-center items-center py-20">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
          </div>
        ) : filteredCustomers.length === 0 ? (
          <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
            <Building2 className="w-16 h-16 text-slate-600 mx-auto mb-4" />
            <p className="text-slate-400 text-lg mb-2">No customers found</p>
            <p className="text-slate-500 text-sm mb-6">
              {customers.length === 0 
                ? "Add your first customer to get started"
                : "Try adjusting your search criteria"}
            </p>
          </Card>
        ) : (
          <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                    <TableHead className="text-slate-300 font-semibold">Customer Name</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Contact Person</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Email</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Phone</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-center">Transactions</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredCustomers.map((customer) => (
                    <TableRow key={customer.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                      <TableCell className="font-medium text-white">{customer.name}</TableCell>
                      <TableCell className="text-slate-300">{customer.contact_person || "-"}</TableCell>
                      <TableCell className="text-slate-300">{customer.email || "-"}</TableCell>
                      <TableCell className="text-slate-300">{customer.phone || "-"}</TableCell>
                      <TableCell className="text-center">
                        <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">
                          {getTransactionCount(customer.name)}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <Link to={createPageUrl('CustomerProfile') + '?id=' + customer.id}>
                            <Button
                              size="sm"
                              variant="ghost"
                              className="text-indigo-400 hover:text-indigo-300 hover:bg-indigo-500/10"
                            >
                              <Eye className="w-4 h-4" />
                            </Button>
                          </Link>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => handleEdit(customer)}
                            className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10"
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => handleDelete(customer.id, customer.name)}
                            className="text-red-400 hover:text-red-300 hover:bg-red-500/10"
                          >
                            <Trash2 className="w-4 h-4" />
                          </Button>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
