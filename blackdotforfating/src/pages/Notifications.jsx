
import React, { useState, useMemo, useEffect } from "react";
import { base44 } from "@/api/base44Client";
import { useQuery } from "@tanstack/react-query";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Textarea } from "@/components/ui/textarea";
import { Bell, Copy, Check, Filter } from "lucide-react";
import { format, addDays, differenceInDays } from "date-fns";

export default function NotificationsPage() {
  const [copiedId, setCopiedId] = useState(null);
  const [daysFilter, setDaysFilter] = useState("30");
  const [editableMessages, setEditableMessages] = useState({});

  const { data: transactions, isLoading } = useQuery({
    queryKey: ['transactions'],
    queryFn: () => base44.entities.Transaction.list('-date_of_transaction'),
    initialData: [],
  });

  const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-AE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  };

  // Filter ongoing transactions and calculate reminder data
  const notificationData = useMemo(() => {
    if (isLoading) return [];
    
    const ongoingTransactions = transactions.filter(t => t.status === "Ongoing");
    const filterDays = parseInt(daysFilter);
    
    return ongoingTransactions
      .map(transaction => {
        const startDate = new Date(transaction.date_of_transaction);
        const endDate = addDays(startDate, transaction.sales_cycle);
        const daysRemaining = differenceInDays(endDate, new Date());
        const grossAmount = transaction.net_amount + (transaction.net_amount * transaction.profit_margin / 100);
        
        const dueDateFormatted = format(endDate, 'EEEE, MMMM do, yyyy');
        
        const message = `Dear ${transaction.customer},

I hope this message finds you well! 

I wanted to send you a friendly reminder regarding your upcoming payment, which is due in ${daysRemaining} day${daysRemaining !== 1 ? 's' : ''} on ${dueDateFormatted}.

The total amount due is AED ${formatCurrency(grossAmount)}.

As always, please feel free to reach out if you have any questions or need any assistance.

Looking forward to continuing our great partnership!

Warm regards`;

        return {
          id: transaction.id,
          transaction_number: transaction.transaction_number,
          customer: transaction.customer,
          grossAmount,
          netAmount: transaction.net_amount,
          profitMargin: transaction.profit_margin,
          startDate,
          endDate,
          daysRemaining,
          dueDateFormatted,
          message,
          urgency: daysRemaining <= 7 ? 'high' : daysRemaining <= 14 ? 'medium' : 'low'
        };
      })
      .filter(item => item.daysRemaining <= filterDays && item.daysRemaining >= 0)
      .sort((a, b) => a.daysRemaining - b.daysRemaining);
  }, [transactions, daysFilter, isLoading]);

  // Initialize editable messages when notification data changes
  useEffect(() => {
    const newMessages = {};
    notificationData.forEach(item => {
      if (!editableMessages[item.id]) {
        newMessages[item.id] = item.message;
      }
    });
    if (Object.keys(newMessages).length > 0) {
      setEditableMessages(prev => ({ ...prev, ...newMessages }));
    }
  }, [notificationData]);

  const updateMessage = (id, newMessage) => {
    setEditableMessages(prev => ({
      ...prev,
      [id]: newMessage
    }));
  };

  const copyToClipboard = (id) => {
    const message = editableMessages[id];
    navigator.clipboard.writeText(message);
    setCopiedId(id);
    setTimeout(() => setCopiedId(null), 2000);
  };

  const getUrgencyBadge = (urgency) => {
    const badges = {
      high: <Badge className="bg-red-500/20 text-red-400 border-red-500/50">Due Soon</Badge>,
      medium: <Badge className="bg-amber-500/20 text-amber-400 border-amber-500/50">Due This Week</Badge>,
      low: <Badge className="bg-blue-500/20 text-blue-400 border-blue-500/50">Upcoming</Badge>
    };
    return badges[urgency] || badges.low;
  };

  if (isLoading) {
    return (
      <div className="flex justify-center items-center min-h-screen">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="max-w-[1400px] mx-auto">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-3 mb-2">
            <div className="p-3 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl">
              <Bell className="w-8 h-8 text-white" />
            </div>
            <div>
              <h2 className="text-3xl font-bold text-white">Payment Reminders</h2>
              <p className="text-slate-400 mt-1">Friendly notifications for upcoming transaction due dates</p>
            </div>
          </div>
        </div>

        {/* Filter Controls */}
        <div className="mb-6 flex items-center gap-4 flex-wrap">
          <div className="flex items-center gap-2">
            <Filter className="w-4 h-4 text-slate-400" />
            <span className="text-slate-300 text-sm">Show transactions due within:</span>
          </div>
          <div className="flex gap-2">
            {['7', '14', '30', '60'].map(days => (
              <Button
                key={days}
                onClick={() => setDaysFilter(days)}
                variant={daysFilter === days ? "default" : "outline"}
                className={daysFilter === days 
                  ? "bg-blue-600 hover:bg-blue-700 text-white" 
                  : "bg-slate-800/50 border-slate-700 text-slate-300 hover:bg-slate-700"
                }
              >
                {days} days
              </Button>
            ))}
          </div>
          <div className="ml-auto">
            <Badge className="bg-slate-700/50 text-slate-300 border-slate-600">
              {notificationData.length} reminder{notificationData.length !== 1 ? 's' : ''}
            </Badge>
          </div>
        </div>

        {/* Notifications List */}
        {notificationData.length === 0 ? (
          <Card className="bg-slate-800/30 border-slate-700 p-12 text-center">
            <Bell className="w-16 h-16 text-slate-600 mx-auto mb-4" />
            <p className="text-slate-400 text-lg mb-2">No upcoming reminders</p>
            <p className="text-slate-500 text-sm">
              All transactions are either completed or not due within the selected timeframe
            </p>
          </Card>
        ) : (
          <div className="space-y-4">
            {notificationData.map((item) => (
              <Card key={item.id} className="bg-slate-800/30 border-slate-700 p-6 hover:border-blue-500/50 transition-colors">
                <div className="flex items-start justify-between mb-4 flex-wrap gap-4">
                  <div className="flex items-center gap-3">
                    <div className="p-2 bg-blue-500/20 rounded-lg">
                      <Bell className="w-5 h-5 text-blue-400" />
                    </div>
                    <div>
                      <h3 className="text-white font-semibold text-lg">{item.customer}</h3>
                      <p className="text-slate-400 text-sm">Transaction #{item.transaction_number}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    {getUrgencyBadge(item.urgency)}
                    <div className="text-right">
                      <p className="text-slate-400 text-xs">Due in</p>
                      <p className={`text-xl font-bold ${
                        item.daysRemaining <= 7 ? 'text-red-400' : 
                        item.daysRemaining <= 14 ? 'text-amber-400' : 
                        'text-blue-400'
                      }`}>
                        {item.daysRemaining} day{item.daysRemaining !== 1 ? 's' : ''}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Transaction Details */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4 p-4 bg-slate-900/50 rounded-lg">
                  <div>
                    <p className="text-slate-400 text-xs mb-1">Gross Amount Due</p>
                    <p className="text-emerald-400 font-semibold text-lg">{formatCurrency(item.grossAmount)} AED</p>
                  </div>
                  <div>
                    <p className="text-slate-400 text-xs mb-1">Net Amount</p>
                    <p className="text-blue-400 font-mono">{formatCurrency(item.netAmount)} AED</p>
                  </div>
                  <div>
                    <p className="text-slate-400 text-xs mb-1">Due Date</p>
                    <p className="text-slate-300">{format(item.endDate, 'dd/MM/yyyy')}</p>
                  </div>
                </div>

                {/* Editable Message */}
                <div className="mb-4">
                  <div className="flex items-center justify-between mb-2">
                    <p className="text-slate-300 text-sm font-medium">Reminder Message (Editable):</p>
                    <div className="flex gap-2">
                      <Button
                        size="sm"
                        onClick={() => copyToClipboard(item.id)}
                        className="bg-slate-700 hover:bg-slate-600 text-white"
                      >
                        {copiedId === item.id ? (
                          <>
                            <Check className="w-4 h-4 mr-2" />
                            Copied!
                          </>
                        ) : (
                          <>
                            <Copy className="w-4 h-4 mr-2" />
                            Copy Message
                          </>
                        )}
                      </Button>
                    </div>
                  </div>
                  <Textarea
                    value={editableMessages[item.id] || item.message}
                    onChange={(e) => updateMessage(item.id, e.target.value)}
                    className="bg-slate-900/50 border-slate-700 text-slate-300 min-h-[280px] font-sans text-sm leading-relaxed"
                    placeholder="Edit your reminder message here..."
                  />
                </div>
              </Card>
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
