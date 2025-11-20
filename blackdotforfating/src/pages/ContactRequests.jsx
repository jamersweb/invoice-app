
import React, { useState } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery, useMutation, useQueryClient } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Mail, Eye, X, Save, MessageSquare } from "lucide-react";
import { format } from "date-fns";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

export default function ContactRequestsPage() {
  const [selectedRequest, setSelectedRequest] = useState(null);
  const [adminNotes, setAdminNotes] = useState("");
  const [requestStatus, setRequestStatus] = useState("New");

  const queryClient = useQueryClient();

  const { data: requests, isLoading } = useQuery({
    queryKey: ['contactRequests'],
    queryFn: () => base44.entities.ContactRequest.list('-created_date'),
    initialData: [],
  });

  const updateMutation = useMutation({
    mutationFn: async ({ id, data }) => {
      return await base44.entities.ContactRequest.update(id, data);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['contactRequests'] });
      setSelectedRequest(null);
    },
  });

  const handleViewRequest = (request) => {
    setSelectedRequest(request);
    setAdminNotes(request.admin_notes || "");
    setRequestStatus(request.status);
  };

  const handleUpdateRequest = () => {
    if (selectedRequest) {
      updateMutation.mutate({
        id: selectedRequest.id,
        data: {
          ...selectedRequest,
          status: requestStatus,
          admin_notes: adminNotes
        }
      });
    }
  };

  const getStatusBadge = (status) => {
    const badges = {
      "New": <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">New</Badge>,
      "In Progress": <Badge className="bg-amber-500/20 text-amber-400 border-amber-500/50">In Progress</Badge>,
      "Resolved": <Badge className="bg-green-500/20 text-green-400 border-green-500/50">Resolved</Badge>
    };
    return badges[status] || badges["New"];
  };

  const formatDateTime = (dateString) => {
    return format(new Date(dateString), 'dd/MM/yyyy HH:mm');
  };

  // Show loading state
  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  const newCount = requests.filter(r => r.status === "New").length;
  const inProgressCount = requests.filter(r => r.status === "In Progress").length;

  return (
    <div className="p-6">
      <div className="max-w-[1800px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-blue-600/20 rounded-xl">
              <Mail className="w-8 h-8 text-blue-400" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Contact Requests</h2>
              <p className="text-slate-400 mt-1">Manage inquiries and messages from potential clients</p>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
          <Card className="bg-gradient-to-br from-blue-800 to-blue-900 border-blue-700 p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-blue-200 text-sm font-medium mb-1">New Requests</p>
                <p className="text-white text-3xl font-bold">{newCount}</p>
              </div>
              <MessageSquare className="w-6 h-6 text-blue-400" />
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-amber-800 to-amber-900 border-amber-700 p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-amber-200 text-sm font-medium mb-1">In Progress</p>
                <p className="text-white text-3xl font-bold">{inProgressCount}</p>
              </div>
              <MessageSquare className="w-6 h-6 text-amber-400" />
            </div>
          </Card>

          <Card className="bg-gradient-to-br from-slate-800 to-slate-900 border-slate-700 p-6">
            <div className="flex justify-between items-start">
              <div>
                <p className="text-slate-300 text-sm font-medium mb-1">Total Requests</p>
                <p className="text-white text-3xl font-bold">{requests.length}</p>
              </div>
              <Mail className="w-6 h-6 text-slate-400" />
            </div>
          </Card>
        </div>

        {/* Requests Table */}
        <div className="rounded-xl border border-slate-700 overflow-hidden bg-slate-800/30 backdrop-blur-sm">
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="bg-slate-800/50 border-slate-700 hover:bg-slate-800/50">
                  <TableHead className="text-slate-300 font-semibold">Date</TableHead>
                  <TableHead className="text-slate-300 font-semibold">Name</TableHead>
                  <TableHead className="text-slate-300 font-semibold">Email</TableHead>
                  <TableHead className="text-slate-300 font-semibold">Subject</TableHead>
                  <TableHead className="text-slate-300 font-semibold text-center">Status</TableHead>
                  <TableHead className="text-slate-300 font-semibold text-center">Actions</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {requests.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={6} className="text-center py-12 text-slate-400">
                      No contact requests yet. Submissions will appear here.
                    </TableCell>
                  </TableRow>
                ) : (
                  requests.map((request) => (
                    <TableRow key={request.id} className="border-slate-700/50 hover:bg-slate-800/30 transition-colors">
                      <TableCell className="text-slate-300 font-mono text-sm">
                        {formatDateTime(request.created_date)}
                      </TableCell>
                      <TableCell className="text-white font-medium">{request.name}</TableCell>
                      <TableCell className="text-slate-300">{request.email}</TableCell>
                      <TableCell className="text-slate-300">{request.subject}</TableCell>
                      <TableCell className="text-center">{getStatusBadge(request.status)}</TableCell>
                      <TableCell className="text-center">
                        <Button
                          size="sm"
                          variant="ghost"
                          onClick={() => handleViewRequest(request)}
                          className="text-blue-400 hover:text-blue-300 hover:bg-blue-500/10"
                        >
                          <Eye className="w-4 h-4" />
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </div>
      </div>

      {/* Request Detail Dialog */}
      <Dialog open={!!selectedRequest} onOpenChange={() => setSelectedRequest(null)}>
        <DialogContent className="bg-slate-800 border-slate-700 text-white max-w-3xl">
          <DialogHeader>
            <DialogTitle className="text-xl font-bold">Contact Request Details</DialogTitle>
            <DialogDescription className="text-slate-400">
              Review and manage this inquiry
            </DialogDescription>
          </DialogHeader>

          {selectedRequest && (
            <div className="space-y-6">
              {/* Request Info */}
              <div className="grid grid-cols-2 gap-4 p-4 bg-slate-900/50 rounded-lg">
                <div>
                  <p className="text-slate-400 text-xs mb-1">Submitted</p>
                  <p className="text-white">{formatDateTime(selectedRequest.created_date)}</p>
                </div>
                <div>
                  <p className="text-slate-400 text-xs mb-1">Status</p>
                  {getStatusBadge(selectedRequest.status)}
                </div>
                <div>
                  <p className="text-slate-400 text-xs mb-1">Name</p>
                  <p className="text-white font-medium">{selectedRequest.name}</p>
                </div>
                <div>
                  <p className="text-slate-400 text-xs mb-1">Email</p>
                  <p className="text-blue-400">{selectedRequest.email}</p>
                </div>
              </div>

              {/* Subject */}
              <div>
                <p className="text-slate-400 text-sm mb-2">Subject</p>
                <p className="text-white font-semibold text-lg">{selectedRequest.subject}</p>
              </div>

              {/* Message */}
              <div>
                <p className="text-slate-400 text-sm mb-2">Message</p>
                <div className="bg-slate-900/50 p-4 rounded-lg">
                  <p className="text-slate-300 whitespace-pre-wrap">{selectedRequest.message}</p>
                </div>
              </div>

              {/* Update Status */}
              <div>
                <p className="text-slate-400 text-sm mb-2">Update Status</p>
                <Select value={requestStatus} onValueChange={setRequestStatus}>
                  <SelectTrigger className="bg-slate-900/50 border-slate-700 text-white">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent className="bg-slate-800 border-slate-700">
                    <SelectItem value="New" className="text-white">New</SelectItem>
                    <SelectItem value="In Progress" className="text-white">In Progress</SelectItem>
                    <SelectItem value="Resolved" className="text-white">Resolved</SelectItem>
                  </SelectContent>
                </Select>
              </div>

              {/* Admin Notes */}
              <div>
                <p className="text-slate-400 text-sm mb-2">Admin Notes (Internal Only)</p>
                <Textarea
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  placeholder="Add internal notes about this request..."
                  rows={4}
                  className="bg-slate-900/50 border-slate-700 text-white placeholder:text-slate-500"
                />
              </div>

              {/* Actions */}
              <div className="flex justify-end gap-3 pt-4 border-t border-slate-700">
                <Button
                  variant="outline"
                  onClick={() => setSelectedRequest(null)}
                  className="bg-slate-900/50 border-slate-700 text-white hover:bg-slate-800"
                >
                  <X className="w-4 h-4 mr-2" />
                  Close
                </Button>
                <Button
                  onClick={handleUpdateRequest}
                  className="bg-blue-600 hover:bg-blue-700"
                >
                  <Save className="w-4 h-4 mr-2" />
                  Save Changes
                </Button>
              </div>
            </div>
          )}
        </DialogContent>
      </Dialog>
    </div>
  );
}
