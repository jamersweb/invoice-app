import Layout from "./Layout.jsx";

import Investments from "./Investments";

import Transactions from "./Transactions";

import ProfitAllocations from "./ProfitAllocations";

import InvestorDashboard from "./InvestorDashboard";

import Investors from "./Investors";

import Dashboard from "./Dashboard";

import Expenses from "./Expenses";

import AuditLog from "./AuditLog";

import Notifications from "./Notifications";

import Customers from "./Customers";

import CustomerProfile from "./CustomerProfile";

import HomePage from "./HomePage";

import ContactPage from "./ContactPage";

import ContactRequests from "./ContactRequests";

import Analytics from "./Analytics";

import { BrowserRouter as Router, Route, Routes, useLocation } from 'react-router-dom';

const PAGES = {
    
    Investments: Investments,
    
    Transactions: Transactions,
    
    ProfitAllocations: ProfitAllocations,
    
    InvestorDashboard: InvestorDashboard,
    
    Investors: Investors,
    
    Dashboard: Dashboard,
    
    Expenses: Expenses,
    
    AuditLog: AuditLog,
    
    Notifications: Notifications,
    
    Customers: Customers,
    
    CustomerProfile: CustomerProfile,
    
    HomePage: HomePage,
    
    ContactPage: ContactPage,
    
    ContactRequests: ContactRequests,
    
    Analytics: Analytics,
    
}

function _getCurrentPage(url) {
    if (url.endsWith('/')) {
        url = url.slice(0, -1);
    }
    let urlLastPart = url.split('/').pop();
    if (urlLastPart.includes('?')) {
        urlLastPart = urlLastPart.split('?')[0];
    }

    const pageName = Object.keys(PAGES).find(page => page.toLowerCase() === urlLastPart.toLowerCase());
    return pageName || Object.keys(PAGES)[0];
}

// Create a wrapper component that uses useLocation inside the Router context
function PagesContent() {
    const location = useLocation();
    const currentPage = _getCurrentPage(location.pathname);
    
    return (
        <Layout currentPageName={currentPage}>
            <Routes>            
                
                    <Route path="/" element={<Investments />} />
                
                
                <Route path="/Investments" element={<Investments />} />
                
                <Route path="/Transactions" element={<Transactions />} />
                
                <Route path="/ProfitAllocations" element={<ProfitAllocations />} />
                
                <Route path="/InvestorDashboard" element={<InvestorDashboard />} />
                
                <Route path="/Investors" element={<Investors />} />
                
                <Route path="/Dashboard" element={<Dashboard />} />
                
                <Route path="/Expenses" element={<Expenses />} />
                
                <Route path="/AuditLog" element={<AuditLog />} />
                
                <Route path="/Notifications" element={<Notifications />} />
                
                <Route path="/Customers" element={<Customers />} />
                
                <Route path="/CustomerProfile" element={<CustomerProfile />} />
                
                <Route path="/HomePage" element={<HomePage />} />
                
                <Route path="/ContactPage" element={<ContactPage />} />
                
                <Route path="/ContactRequests" element={<ContactRequests />} />
                
                <Route path="/Analytics" element={<Analytics />} />
                
            </Routes>
        </Layout>
    );
}

export default function Pages() {
    return (
        <Router>
            <PagesContent />
        </Router>
    );
}