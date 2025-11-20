import React, { useState, useEffect } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Building2, Upload, X, FileText, Edit, Trash2, Download, CheckCircle2, Clock, XCircle, ArrowLeft } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { format } from "date-fns";
import { useNavigate } from "react-router-dom";
import { createPageUrl } from "@/utils";

export default function CustomerProfilePage() {
  const urlParams = new URLSearchParams(window.location.search);
  const customerId = urlParams.get('id');
  const navigate = useNavigate();

  const [isUploadOpen, setIsUploadOpen] = useState(false);
  const [editingDocument, setEditingDocument] = useState(null);
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploadForm, setUploadForm] = useState({
    document_type: "",
    related_transaction_number: "",
    notes: "",
    cheque_status: "",
    cheque_received_date: "",
    cheque_given_back_date: ""
  });

  const queryClient = useQueryClient();

  const { data: customer, isLoading: customerLoading } = useQuery({
    queryKey: ['customer', customerId],
    queryFn: async () => {
      const customers = await base44.entities.Customer.list();
      return customers.find(c => c.id === customerId);
    },
    enabled: !!customerId,
  });

  const { data: documents, isLoading: documentsLoading } = useQuery({
    queryKey: ['customerDocuments', customerId],
    queryFn: async () => {
      const allDocs = await base44.entities.CustomerDocument.list('-upload_date');
      return allDocs.filter(doc => doc.customer_id === customerId);
    },
    enabled: !!customerId,
    initialData: [],
  });

  const { data: transactions } = useQuery({
    queryKey: ['transactions'],
    queryFn: () => base44.entities.Transaction.list(),
    initialData: [],
  });

  // Filter transactions for this customer
  const customerTransactions = transactions.filter(t => t.customer === customer?.name);

  const uploadMutation = useMutation({
    mutationFn: async (docData) => {
      const user = await base44.auth.me();
      const newDoc = await base44.entities.CustomerDocument.create(docData);
      
      await base44.entities.AuditLog.create({
        entity_name: "CustomerDocument",
        entity_id: newDoc.id,
        action: "created",
        user_email: user.email,
        description: `Uploaded document: ${docData.document_type} for customer ${customer.name}`,
        new_data: JSON.stringify(docData)
      });
      
      return newDoc;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customerDocuments', customerId] });
      handleCancelUpload();
    },
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, docData }) => {
      const user = await base44.auth.me();
      const oldDoc = documents.find(d => d.id === id);
      
      const updated = await base44.entities.CustomerDocument.update(id, docData);
      
      await base44.entities.AuditLog.create({
        entity_name: "CustomerDocument",
        entity_id: id,
        action: "updated",
        user_email: user.email,
        description: `Updated document: ${docData.document_type} for customer ${customer.name}`,
        old_data: JSON.stringify(oldDoc),
        new_data: JSON.stringify(docData)
      });
      
      return updated;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customerDocuments', customerId] });
      handleCancelUpload();
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async (id) => {
      const user = await base44.auth.me();
      const doc = documents.find(d => d.id === id);
      
      await base44.entities.CustomerDocument.delete(id);
      
      await base44.entities.AuditLog.create({
        entity_name: "CustomerDocument",
        entity_id: id,
        action: "deleted",
        user_email: user.email,
        description: `Deleted document: ${doc.document_type} for customer ${customer.name}`,
        old_data: JSON.stringify(doc)
      });
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['customerDocuments', customerId] });
    },
  });

  const handleFileSelect = (e) => {
    setSelectedFile(e.target.files[0]);
  };

  const handleUploadSubmit = async (e) => {
    e.preventDefault();

    let fileUrl = editingDocument?.file_url;
    let fileName = editingDocument?.file_name;

    if (selectedFile) {
      const { file_url } = await base44.integrations.Core.UploadFile({ file: selectedFile });
      fileUrl = file_url;
      fileName = selectedFile.name;
    }

    const docData = {
      customer_id: customerId,
      document_type: uploadForm.document_type,
      file_url: fileUrl,
      file_name: fileName,
      upload_date: new Date().toISOString().split('T')[0],
      related_transaction_number: uploadForm.related_transaction_number ? parseInt(uploadForm.related_transaction_number) : null,
      notes: uploadForm.notes || null,
      cheque_status: uploadForm.document_type === "Cheque" ? uploadForm.cheque_status : null,
      cheque_received_date: uploadForm.document_type === "Cheque" && uploadForm.cheque_received_date ? uploadForm.cheque_received_date : null,
      cheque_given_back_date: uploadForm.document_type === "Cheque" && uploadForm.cheque_given_back_date ? uploadForm.cheque_given_back_date : null
    };

    if (editingDocument) {
      updateMutation.mutate({ id: editingDocument.id, docData });
    } else {
      uploadMutation.mutate(docData);
    }
  };

  const handleEdit = (doc) => {
    setEditingDocument(doc);
    setUploadForm({
      document_type: doc.document_type,
      related_transaction_number: doc.related_transaction_number?.toString() || "",
      notes: doc.notes || "",
      cheque_status: doc.cheque_status || "",
      cheque_received_date: doc.cheque_received_date || "",
      cheque_given_back_date: doc.cheque_given_back_date || ""
    });
    setIsUploadOpen(true);
  };

  const handleDelete = (id) => {
    if (confirm('Are you sure you want to delete this document?')) {
      deleteMutation.mutate(id);
    }
  };

  const handleCancelUpload = () => {
    setIsUploadOpen(false);
    setEditingDocument(null);
    setSelectedFile(null);
    setUploadForm({
      document_type: "",
      related_transaction_number: "",
      notes: "",
      cheque_status: "",
      cheque_received_date: "",
      cheque_given_back_date: ""
    });
  };

  const getChequeStatusBadge = (status) => {
    switch(status) {
      case "Received":
        return <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50 flex items-center gap-1">
          <Clock className="w-3 h-3" />
          Received
        </Badge>;
      case "Given Back":
        return <Badge className="bg-green-500/20 text-green-400 border-green-500/50 flex items-center gap-1">
          <CheckCircle2 className="w-3 h-3" />
          Given Back
        </Badge>;
      case "Pending":
        return <Badge className="bg-amber-500/20 text-amber-400 border-amber-500/50 flex items-center gap-1">
          <XCircle className="w-3 h-3" />
          Pending
        </Badge>;
      default:
        return null;
    }
  };

  if (customerLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
      </div>
    );
  }

  if (!customer) {
    return (
      <div className="p-6">
        <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
          <Building2 className="w-16 h-16 text-slate-600 mx-auto mb-4" />
          <p className="text-slate-400 text-lg">Customer not found</p>
        </Card>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <Button
                variant="ghost"
                onClick={() => navigate(createPageUrl('Customers'))}
                className="text-slate-400 hover:text-white hover:bg-slate-800"
              >
                <ArrowLeft className="w-4 h-4 mr-2" />
                Back
              </Button>
              <div className="p-3 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-xl">
                <Building2 className="w-8 h-8 text-white" />
              </div>
              <div>
                <h2 className="text-3xl font-bold text-white">{customer.name}</h2>
                <p className="text-slate-400 mt-1">Customer Profile & Documents</p>
              </div>
            </div>
            {!isUploadOpen && (
              <Button
                onClick={() => setIsUploadOpen(true)}
                className="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20"
              >
                <Upload className="w-4 h-4 mr-2" />
                Upload Document
              </Button>
            )}
          </div>
        </div>

        {/* Customer Info */}
        <Card className="bg-slate-800/30 border-slate-700 p-6 mb-8">
          <h3 className="text-white text-lg font-semibold mb-4">Customer Information</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <p className="text-slate-400 text-sm">Contact Person</p>
              <p className="text-white">{customer.contact_person || "-"}</p>
            </div>
            <div>
              <p className="text-slate-400 text-sm">Email</p>
              <p className="text-white">{customer.email || "-"}</p>
            </div>
            <div>
              <p className="text-slate-400 text-sm">Phone</p>
              <p className="text-white">{customer.phone || "-"}</p>
            </div>
            <div>
              <p className="text-slate-400 text-sm">Address</p>
              <p className="text-white">{customer.address || "-"}</p>
            </div>
            {customer.notes && (
              <div className="md:col-span-2">
                <p className="text-slate-400 text-sm">Notes</p>
                <p className="text-white">{customer.notes}</p>
              </div>
            )}
          </div>
        </Card>

        {/* Upload Form */}
        {isUploadOpen && (
          <Card className="bg-slate-800/50 border-slate-700 p-6 mb-8">
            <div className="flex justify-between items-center mb-4">
              <h3 className="text-white text-lg font-semibold flex items-center gap-2">
                {editingDocument ? <Edit className="w-5 h-5 text-blue-400" /> : <Upload className="w-5 h-5 text-blue-400" />}
                {editingDocument ? "Edit Document" : "Upload New Document"}
              </h3>
              <Button variant="ghost" size="icon" onClick={handleCancelUpload} className="text-slate-400 hover:text-white">
                <X className="w-4 h-4" />
              </Button>
            </div>

            <form onSubmit={handleUploadSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label className="text-slate-300">Document Type *</Label>
                  <Select
                    value={uploadForm.document_type}
                    onValueChange={(value) => setUploadForm({ ...uploadForm, document_type: value })}
                    required
                  >
                    <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                      <SelectValue placeholder="Select type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="Master Agreement">Master Agreement</SelectItem>
                      <SelectItem value="Purchase Agreement">Purchase Agreement</SelectItem>
                      <SelectItem value="Invoice">Invoice</SelectItem>
                      <SelectItem value="Disbursement Swift">Disbursement Swift</SelectItem>
                      <SelectItem value="Payment Received Swift">Payment Received Swift</SelectItem>
                      <SelectItem value="Company Documents">Company Documents</SelectItem>
                      <SelectItem value="Cheque">Cheque</SelectItem>
                      <SelectItem value="Other">Other</SelectItem>
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label className="text-slate-300">Related Transaction #</Label>
                  <Select
                    value={uploadForm.related_transaction_number}
                    onValueChange={(value) => setUploadForm({ ...uploadForm, related_transaction_number: value })}
                  >
                    <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                      <SelectValue placeholder="Select transaction (optional)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value={null}>None</SelectItem>
                      {customerTransactions.map((t) => (
                        <SelectItem key={t.id} value={t.transaction_number.toString()}>
                          #{t.transaction_number} - {format(new Date(t.date_of_transaction), 'dd/MM/yyyy')} - {t.net_amount.toLocaleString()} AED
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                {uploadForm.document_type === "Cheque" && (
                  <>
                    <div className="space-y-2">
                      <Label className="text-slate-300">Cheque Status *</Label>
                      <Select
                        value={uploadForm.cheque_status}
                        onValueChange={(value) => setUploadForm({ ...uploadForm, cheque_status: value })}
                        required
                      >
                        <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                          <SelectValue placeholder="Select status" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="Pending">Pending</SelectItem>
                          <SelectItem value="Received">Received</SelectItem>
                          <SelectItem value="Given Back">Given Back</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label className="text-slate-300">Cheque Received Date</Label>
                      <Input
                        type="date"
                        value={uploadForm.cheque_received_date}
                        onChange={(e) => setUploadForm({ ...uploadForm, cheque_received_date: e.target.value })}
                        className="bg-slate-900/50 border-slate-700 text-white"
                      />
                    </div>

                    <div className="space-y-2">
                      <Label className="text-slate-300">Cheque Given Back Date</Label>
                      <Input
                        type="date"
                        value={uploadForm.cheque_given_back_date}
                        onChange={(e) => setUploadForm({ ...uploadForm, cheque_given_back_date: e.target.value })}
                        className="bg-slate-900/50 border-slate-700 text-white"
                      />
                    </div>
                  </>
                )}

                <div className="space-y-2 md:col-span-2">
                  <Label className="text-slate-300">File {!editingDocument && "*"}</Label>
                  <Input
                    type="file"
                    onChange={handleFileSelect}
                    required={!editingDocument}
                    className="bg-slate-900/50 border-slate-700 text-white file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700"
                  />
                  {editingDocument && <p className="text-slate-400 text-xs">Leave empty to keep current file</p>}
                </div>

                <div className="space-y-2 md:col-span-2">
                  <Label className="text-slate-300">Notes</Label>
                  <Textarea
                    value={uploadForm.notes}
                    onChange={(e) => setUploadForm({ ...uploadForm, notes: e.target.value })}
                    placeholder="Additional notes..."
                    className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                  />
                </div>
              </div>

              <div className="flex justify-end gap-3 pt-4">
                <Button type="button" variant="outline" onClick={handleCancelUpload} className="bg-slate-900/50 border-slate-700 text-white hover:bg-slate-800">
                  Cancel
                </Button>
                <Button type="submit" className="bg-indigo-600 hover:bg-indigo-700 text-white">
                  {editingDocument ? "Update Document" : "Upload Document"}
                </Button>
              </div>
            </form>
          </Card>
        )}

        {/* Documents Table */}
        <Card className="bg-slate-800/30 border-slate-700">
          <div className="p-6">
            <h3 className="text-white text-lg font-semibold mb-4 flex items-center gap-2">
              <FileText className="w-5 h-5 text-indigo-400" />
              Documents ({documents.length})
            </h3>
          </div>

          {documentsLoading ? (
            <div className="flex justify-center items-center py-20">
              <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-indigo-500"></div>
            </div>
          ) : documents.length === 0 ? (
            <div className="p-12 text-center">
              <FileText className="w-16 h-16 text-slate-600 mx-auto mb-4" />
              <p className="text-slate-400 text-lg mb-2">No documents uploaded yet</p>
              <p className="text-slate-500 text-sm">Upload your first document to get started</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <Table>
                <TableHeader>
                  <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                    <TableHead className="text-slate-300 font-semibold">Type</TableHead>
                    <TableHead className="text-slate-300 font-semibold">File Name</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-center">Transaction #</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-center">Cheque Status</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Upload Date</TableHead>
                    <TableHead className="text-slate-300 font-semibold">Notes</TableHead>
                    <TableHead className="text-slate-300 font-semibold text-right">Actions</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {documents.map((doc) => (
                    <TableRow key={doc.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                      <TableCell>
                        <Badge className="bg-indigo-500/20 text-indigo-400 border-indigo-500/50">
                          {doc.document_type}
                        </Badge>
                      </TableCell>
                      <TableCell className="text-white font-medium">{doc.file_name}</TableCell>
                      <TableCell className="text-center">
                        {doc.related_transaction_number ? (
                          <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">
                            #{doc.related_transaction_number}
                          </Badge>
                        ) : (
                          <span className="text-slate-500">-</span>
                        )}
                      </TableCell>
                      <TableCell className="text-center">
                        {doc.document_type === "Cheque" && doc.cheque_status ? (
                          <div className="flex flex-col items-center gap-1">
                            {getChequeStatusBadge(doc.cheque_status)}
                            {doc.cheque_received_date && (
                              <span className="text-xs text-slate-400">
                                Rcvd: {format(new Date(doc.cheque_received_date), 'dd/MM/yy')}
                              </span>
                            )}
                            {doc.cheque_given_back_date && (
                              <span className="text-xs text-slate-400">
                                Back: {format(new Date(doc.cheque_given_back_date), 'dd/MM/yy')}
                              </span>
                            )}
                          </div>
                        ) : (
                          <span className="text-slate-500">-</span>
                        )}
                      </TableCell>
                      <TableCell className="text-slate-300">{format(new Date(doc.upload_date), 'dd/MM/yyyy')}</TableCell>
                      <TableCell className="text-slate-300 max-w-xs truncate">{doc.notes || "-"}</TableCell>
                      <TableCell className="text-right">
                        <div className="flex justify-end gap-2">
                          <a href={doc.file_url} target="_blank" rel="noopener noreferrer">
                            <Button
                              size="sm"
                              variant="ghost"
                              className="text-green-400 hover:text-green-300 hover:bg-green-500/10"
                            >
                              <Download className="w-4 h-4" />
                            </Button>
                          </a>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => handleEdit(doc)}
                            className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10"
                          >
                            <Edit className="w-4 h-4" />
                          </Button>
                          <Button
                            size="sm"
                            variant="ghost"
                            onClick={() => handleDelete(doc.id)}
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
          )}
        </Card>
      </div>
    </div>
  );
}