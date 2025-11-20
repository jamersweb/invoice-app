
import React, { useEffect, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { createPageUrl } from "@/utils";
import { base44 } from "@/api/base44Client";
import { LayoutDashboard, FileSpreadsheet, Receipt, Calculator, Users, UserCircle, Bell, FileText, Menu, X, Building2, Mail, LogOut, ChevronDown, DollarSign, TrendingUp } from "lucide-react";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

export default function Layout({ children, currentPageName }) {
  const location = useLocation();
  const navigate = useNavigate();
  const [currentUser, setCurrentUser] = useState(null);
  const [loadingUser, setLoadingUser] = useState(true);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  // Fetch user ONCE on mount only
  useEffect(() => {
    const fetchUser = async () => {
      try {
        const authenticated = await base44.auth.isAuthenticated();
        
        // If not authenticated and not on public pages, redirect to login
        const publicPages = ['HomePage', 'ContactPage'];
        if (!authenticated && !publicPages.includes(currentPageName)) {
          base44.auth.redirectToLogin(window.location.pathname);
          return;
        }

        // If not authenticated but on public page, allow access
        if (!authenticated && publicPages.includes(currentPageName)) {
          setCurrentUser(null);
          setLoadingUser(false);
          return;
        }

        // User is authenticated, fetch their details
        const user = await base44.auth.me();
        
        // Generate investor_id if user doesn't have one (for non-admin users)
        if (user.role !== 'admin' && !user.investor_id) {
          const investorId = crypto.randomUUID();
          await base44.auth.updateMe({ investor_id: investorId });
          user.investor_id = investorId;
        }
        
        setCurrentUser(user);
        setLoadingUser(false);

      } catch (error) {
        console.error("Authentication error:", error);
        const publicPages = ['HomePage', 'ContactPage'];
        if (!publicPages.includes(currentPageName)) {
          base44.auth.redirectToLogin(window.location.pathname);
        }
        setLoadingUser(false);
      }
    };
    fetchUser();
  }, []); // Empty dependency array - run once on mount only

  // Separate effect for authorization checks - only runs when user is loaded and page changes
  useEffect(() => {
    if (loadingUser || !currentUser) return;

    const adminOnlyPages = ['Dashboard', 'Analytics', 'Investments', 'Transactions', 'Expenses', 'ProfitAllocations', 'Customers', 'CustomerProfile', 'Investors', 'Notifications', 'ContactRequests', 'AuditLog'];
    
    // Check if non-admin is trying to access admin page
    if (currentUser.role !== 'admin' && adminOnlyPages.includes(currentPageName)) {
      navigate(createPageUrl('InvestorDashboard') + '?id=' + currentUser.investor_id, { replace: true });
      return;
    }

    // ONLY redirect from HomePage if we're ACTUALLY on the HomePage path
    // This prevents false redirects when currentPageName temporarily shows 'HomePage' during navigation
    if (currentPageName === 'HomePage' && location.pathname === createPageUrl('HomePage')) {
      if (currentUser.role === 'admin') {
        navigate(createPageUrl('Dashboard'), { replace: true });
      } else {
        navigate(createPageUrl('InvestorDashboard') + '?id=' + currentUser.investor_id, { replace: true });
      }
      return;
    }
  }, [loadingUser, currentUser, currentPageName, location.pathname, navigate]);

  const isActive = (pageName) => {
    return location.pathname === createPageUrl(pageName);
  };

  const isAdmin = currentUser?.role === 'admin';

  const closeMobileMenu = () => setMobileMenuOpen(false);

  const handleLogout = () => {
    base44.auth.logout();
  };

  const NavLink = ({ to, icon: Icon, label, active }) => (
    <Link
      to={to}
      onClick={closeMobileMenu}
      className={`flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-all duration-200 ${
        active
          ? "bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg"
          : "text-slate-300 hover:text-white hover:bg-slate-800/50"
      }`}
    >
      <Icon className="w-4 h-4" />
      <span className="text-sm">{label}</span>
    </Link>
  );

  const DropdownNavLink = ({ icon: Icon, label, active, children }) => (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <button
          className={`flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-all duration-200 ${
            active
              ? "bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-lg"
              : "text-slate-300 hover:text-white hover:bg-slate-800/50"
          }`}
        >
          <Icon className="w-4 h-4" />
          <span className="text-sm">{label}</span>
          <ChevronDown className="w-3 h-3" />
        </button>
      </DropdownMenuTrigger>
      <DropdownMenuContent className="bg-slate-800 border-slate-700">
        {children}
      </DropdownMenuContent>
    </DropdownMenu>
  );

  // Show loading state while checking authentication
  if (loadingUser) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950 flex items-center justify-center">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
      {/* Global Header */}
      <header className="bg-slate-900/50 border-b border-slate-700/50 backdrop-blur-sm sticky top-0 z-50">
        <div className="max-w-[1800px] mx-auto px-4 sm:px-6 py-3">
          <div className="flex items-center justify-between">
            {/* Logo/Brand with BLACK DOT */}
            <Link to={createPageUrl("HomePage")} className="flex items-center gap-2 sm:gap-3 group">
              <div className="relative">
                <div className="w-8 h-8 sm:w-10 sm:h-10 bg-black rounded-full shadow-lg border border-slate-600 transition-transform group-hover:scale-110" />
                <div className="absolute inset-0 w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-slate-700/20 to-slate-900/20 rounded-full blur-md group-hover:blur-lg transition-all" />
              </div>
              <div>
                <h1 className="text-base sm:text-lg font-bold text-white group-hover:text-blue-400 transition-colors">
                  BlackDot Forfaiting
                </h1>
              </div>
            </Link>

            {currentUser && (
              <>
                {/* Desktop Navigation */}
                <nav className="hidden lg:flex items-center gap-2">
                  {isAdmin && (
                    <>
                      <NavLink
                        to={createPageUrl("Dashboard")}
                        icon={LayoutDashboard}
                        label="Dashboard"
                        active={isActive("Dashboard")}
                      />
                      
                      <NavLink
                        to={createPageUrl("Analytics")}
                        icon={TrendingUp}
                        label="Analytics"
                        active={isActive("Analytics")}
                      />
                      
                      <NavLink
                        to={createPageUrl("Investments")}
                        icon={Users}
                        label="Investments"
                        active={isActive("Investments")}
                      />

                      <DropdownNavLink
                        icon={DollarSign}
                        label="Finance"
                        active={isActive("Transactions") || isActive("Expenses") || isActive("ProfitAllocations")}
                      >
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("Transactions")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <FileSpreadsheet className="w-4 h-4" />
                            Transactions
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("Expenses")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <Receipt className="w-4 h-4" />
                            Expenses
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("ProfitAllocations")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <Calculator className="w-4 h-4" />
                            Allocations
                          </Link>
                        </DropdownMenuItem>
                      </DropdownNavLink>

                      <DropdownNavLink
                        icon={Building2}
                        label="Customers"
                        active={isActive("Customers") || isActive("CustomerProfile")}
                      >
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("Customers")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <Building2 className="w-4 h-4" />
                            All Customers
                          </Link>
                        </DropdownMenuItem>
                      </DropdownNavLink>

                      <NavLink
                        to={createPageUrl("Investors")}
                        icon={UserCircle}
                        label="Investors"
                        active={isActive("Investors")}
                      />

                      <DropdownNavLink
                        icon={FileText}
                        label="Admin"
                        active={isActive("ContactRequests") || isActive("Notifications") || isActive("AuditLog")}
                      >
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("ContactRequests")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <Mail className="w-4 h-4" />
                            Contacts
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("Notifications")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <Bell className="w-4 h-4" />
                            Reminders
                          </Link>
                        </DropdownMenuItem>
                        <DropdownMenuItem asChild>
                          <Link
                            to={createPageUrl("AuditLog")}
                            className="flex items-center gap-2 px-3 py-2 text-white hover:bg-slate-700 cursor-pointer"
                            onClick={closeMobileMenu}
                          >
                            <FileText className="w-4 h-4" />
                            Audit
                          </Link>
                        </DropdownMenuItem>
                      </DropdownNavLink>
                    </>
                  )}

                  {!isAdmin && currentUser && (
                    <NavLink
                      to={createPageUrl("InvestorDashboard") + '?id=' + (currentUser.investor_id || '')}
                      icon={UserCircle}
                      label="My Dashboard"
                      active={isActive("InvestorDashboard")}
                    />
                  )}

                  {/* Logout Button - Desktop */}
                  <Button
                    onClick={handleLogout}
                    variant="ghost"
                    className="ml-2 text-slate-300 hover:text-white hover:bg-red-500/10 hover:border-red-500/50 border border-transparent transition-all"
                  >
                    <LogOut className="w-4 h-4 mr-2" />
                    <span className="text-sm">Sign Out</span>
                  </Button>
                </nav>

                {/* Mobile Menu Button */}
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                  className="lg:hidden text-white"
                >
                  {mobileMenuOpen ? <X className="w-5 h-5" /> : <Menu className="w-5 h-5" />}
                </Button>
              </>
            )}
          </div>
        </div>

        {/* Mobile Navigation Menu */}
        {mobileMenuOpen && currentUser && (
          <div className="lg:hidden border-t border-slate-700/50 bg-slate-900/95 backdrop-blur-sm">
            <nav className="max-w-[1800px] mx-auto px-4 py-4 space-y-1">
              {isAdmin && (
                <>
                  <NavLink
                    to={createPageUrl("Dashboard")}
                    icon={LayoutDashboard}
                    label="Dashboard"
                    active={isActive("Dashboard")}
                  />
                  <NavLink
                    to={createPageUrl("Analytics")}
                    icon={TrendingUp}
                    label="Analytics"
                    active={isActive("Analytics")}
                  />
                  <NavLink
                    to={createPageUrl("Investments")}
                    icon={Users}
                    label="Investments"
                    active={isActive("Investments")}
                  />
                  
                  {/* Finance Section - Mobile */}
                  <div className="pl-2 border-l-2 border-slate-700 ml-2 space-y-1">
                    <p className="text-xs text-slate-500 font-semibold uppercase mb-2">Finance</p>
                    <NavLink
                      to={createPageUrl("Transactions")}
                      icon={FileSpreadsheet}
                      label="Transactions"
                      active={isActive("Transactions")}
                    />
                    <NavLink
                      to={createPageUrl("Expenses")}
                      icon={Receipt}
                      label="Expenses"
                      active={isActive("Expenses")}
                    />
                    <NavLink
                      to={createPageUrl("ProfitAllocations")}
                      icon={Calculator}
                      label="Profit Allocations"
                      active={isActive("ProfitAllocations")}
                    />
                  </div>

                  {/* Customers Section - Mobile */}
                  <div className="pl-2 border-l-2 border-slate-700 ml-2 space-y-1">
                    <p className="text-xs text-slate-500 font-semibold uppercase mb-2">Customers</p>
                    <NavLink
                      to={createPageUrl("Customers")}
                      icon={Building2}
                      label="All Customers"
                      active={isActive("Customers")}
                    />
                  </div>

                  <NavLink
                    to={createPageUrl("Investors")}
                    icon={UserCircle}
                    label="Investor Dashboards"
                    active={isActive("Investors")}
                  />

                  {/* Admin Section - Mobile */}
                  <div className="pl-2 border-l-2 border-slate-700 ml-2 space-y-1">
                    <p className="text-xs text-slate-500 font-semibold uppercase mb-2">Admin</p>
                    <NavLink
                      to={createPageUrl("ContactRequests")}
                      icon={Mail}
                      label="Contact Requests"
                      active={isActive("ContactRequests")}
                    />
                    <NavLink
                      to={createPageUrl("Notifications")}
                      icon={Bell}
                      label="Payment Reminders"
                      active={isActive("Notifications")}
                    />
                    <NavLink
                      to={createPageUrl("AuditLog")}
                      icon={FileText}
                      label="Audit Log"
                      active={isActive("AuditLog")}
                    />
                  </div>
                </>
              )}

              {!isAdmin && currentUser && (
                <NavLink
                  to={createPageUrl("InvestorDashboard") + '?id=' + (currentUser.investor_id || '')}
                  icon={UserCircle}
                  label="My Dashboard"
                  active={isActive("InvestorDashboard")}
                />
              )}

              {/* Logout Button - Mobile */}
              <button
                onClick={handleLogout}
                className="w-full flex items-center gap-2 px-3 py-2 rounded-lg font-medium transition-all duration-200 text-red-400 hover:text-white hover:bg-red-500/10 border border-red-500/30 mt-4"
              >
                <LogOut className="w-4 h-4" />
                <span className="text-sm">Sign Out</span>
              </button>
            </nav>
          </div>
        )}
      </header>

      {/* Page Content */}
      <main>
        {children}
      </main>
    </div>
  );
}
