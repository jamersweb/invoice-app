import React from "react";
import { base44 } from "@/api/base44Client";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Link } from "react-router-dom";
import { createPageUrl } from "@/utils";
import { 
  Shield, 
  Target,
  Handshake,
  Lock,
  CheckCircle2,
  Mail
} from "lucide-react";

export default function HomePage() {
  return (
    <div className="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden">
      {/* Animated Background - Lightened */}
      <div className="absolute inset-0 overflow-hidden">
        {/* Gradient overlays */}
        <div className="absolute inset-0 bg-gradient-to-br from-blue-600/8 via-purple-600/8 to-emerald-600/8" />
        
        {/* Animated circles */}
        <div className="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/15 rounded-full blur-3xl animate-pulse" />
        <div className="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/15 rounded-full blur-3xl animate-pulse" style={{ animationDelay: '1s' }} />
        <div className="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-500/10 rounded-full blur-3xl animate-pulse" style={{ animationDelay: '2s' }} />
        
        {/* Floating dots */}
        <div className="absolute top-1/4 left-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping" style={{ animationDuration: '3s' }} />
        <div className="absolute top-1/3 right-1/4 w-2 h-2 bg-purple-400/40 rounded-full animate-ping" style={{ animationDuration: '4s', animationDelay: '1s' }} />
        <div className="absolute bottom-1/4 left-1/4 w-2 h-2 bg-emerald-400/40 rounded-full animate-ping" style={{ animationDuration: '5s', animationDelay: '2s' }} />
        <div className="absolute top-1/2 right-1/3 w-2 h-2 bg-blue-400/40 rounded-full animate-ping" style={{ animationDuration: '4s', animationDelay: '1.5s' }} />
        
        {/* Grid pattern */}
        <div className="absolute inset-0" style={{
          backgroundImage: `linear-gradient(rgba(148, 163, 184, 0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(148, 163, 184, 0.05) 1px, transparent 1px)`,
          backgroundSize: '50px 50px'
        }} />
      </div>

      {/* Hero Section */}
      <div className="relative">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 py-16 sm:py-24">
          <div className="text-center">
            {/* Logo - BLACK DOT */}
            <div className="flex justify-center mb-8">
              <div className="relative">
                <div className="w-20 h-20 bg-black rounded-full shadow-2xl border-2 border-slate-600" />
                <div className="absolute inset-0 w-20 h-20 bg-gradient-to-br from-slate-700/30 to-slate-900/30 rounded-full blur-xl animate-pulse" />
              </div>
            </div>
            
            <h1 className="text-3xl sm:text-5xl md:text-6xl font-bold text-white mb-6 leading-tight">
              Welcome to<br />
              <span className="bg-gradient-to-r from-blue-400 via-purple-400 to-emerald-400 bg-clip-text text-transparent">
                BlackDot Forfaiting Services
              </span>
            </h1>
            
            <p className="text-lg sm:text-xl text-slate-300 mb-4 max-w-3xl mx-auto leading-relaxed">
              We specialize in purchasing trade receivables to help businesses unlock liquidity, improve cash flow, and reduce payment risk.
            </p>
            
            <p className="text-base sm:text-lg text-slate-400 mb-10 max-w-2xl mx-auto">
              Our focus is on real transactions, clear structures, and measurable results.
            </p>
            
            {/* Simplified CTA - Login and Contact buttons */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center">
              <Button
                onClick={() => base44.auth.redirectToLogin(window.location.pathname)}
                size="lg"
                className="bg-white hover:bg-slate-100 text-slate-900 shadow-2xl text-base sm:text-lg px-8 py-6 rounded-xl font-semibold transition-all hover:scale-105"
              >
                <Lock className="w-5 h-5 mr-2" />
                Login
              </Button>
              
              <Link to={createPageUrl('ContactPage')}>
                <Button
                  size="lg"
                  variant="outline"
                  className="bg-transparent border-2 border-white/30 hover:border-white/50 text-white shadow-2xl text-base sm:text-lg px-8 py-6 rounded-xl font-semibold transition-all hover:scale-105 w-full sm:w-auto"
                >
                  <Mail className="w-5 h-5 mr-2" />
                  Contact Us
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </div>

      {/* What We Do Section */}
      <div className="relative max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <div className="text-center mb-12">
          <h2 className="text-2xl sm:text-4xl font-bold text-white mb-4">What We Do</h2>
          <div className="w-20 h-1 bg-gradient-to-r from-blue-400 to-purple-400 mx-auto rounded-full mb-6" />
        </div>

        <Card className="bg-slate-800/30 backdrop-blur-sm border-slate-700/50 p-8 sm:p-12 mb-16">
          <p className="text-slate-300 text-base sm:text-lg leading-relaxed text-center max-w-4xl mx-auto">
            <span className="font-semibold text-white">BlackDot Forfaiting Services (BDFS)</span> provides forfaiting and structured trade finance solutions by purchasing receivables generated from cross-border and domestic trade. We work with exporters, suppliers, and intermediaries who need early payment against receivables backed by Letters of Credit, SBLCs, or confirmed payment obligations.
          </p>
        </Card>

        {/* Three Pillars */}
        <div className="mb-12">
          <h2 className="text-2xl sm:text-4xl font-bold text-white mb-12 text-center">Our Three Pillars</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6 sm:gap-8">
            {/* Pillar 1 - Transparency */}
            <Card className="bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-blue-500/50 transition-all duration-300 hover:transform hover:scale-105 group">
              <div className="flex justify-center mb-6">
                <div className="p-4 bg-blue-500/10 rounded-2xl group-hover:bg-blue-500/20 transition-colors">
                  <Shield className="w-10 h-10 text-blue-400" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-white mb-4 text-center">1. Transparency</h3>
              <div className="space-y-3 text-slate-300 leading-relaxed">
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                  <span>Every transaction is conducted with full visibility</span>
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                  <span>All terms, fees, and timelines are clearly defined</span>
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-blue-400 flex-shrink-0 mt-0.5" />
                  <span>No hidden costs or unclear conditions</span>
                </p>
              </div>
            </Card>

            {/* Pillar 2 - Discipline */}
            <Card className="bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-purple-500/50 transition-all duration-300 hover:transform hover:scale-105 group">
              <div className="flex justify-center mb-6">
                <div className="p-4 bg-purple-500/10 rounded-2xl group-hover:bg-purple-500/20 transition-colors">
                  <Target className="w-10 h-10 text-purple-400" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-white mb-4 text-center">2. Discipline</h3>
              <div className="space-y-3 text-slate-300 leading-relaxed">
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" />
                  <span>Structured due diligence process</span>
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" />
                  <span>Credit assessment for every deal</span>
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-purple-400 flex-shrink-0 mt-0.5" />
                  <span>Responsible capital deployment and consistent performance management</span>
                </p>
              </div>
            </Card>

            {/* Pillar 3 - Partnership */}
            <Card className="bg-slate-800/40 backdrop-blur-sm border-slate-700/50 p-8 hover:border-emerald-500/50 transition-all duration-300 hover:transform hover:scale-105 group">
              <div className="flex justify-center mb-6">
                <div className="p-4 bg-emerald-500/10 rounded-2xl group-hover:bg-emerald-500/20 transition-colors">
                  <Handshake className="w-10 h-10 text-emerald-400" />
                </div>
              </div>
              <h3 className="text-2xl font-bold text-white mb-4 text-center">3. Partnership</h3>
              <div className="space-y-3 text-slate-300 leading-relaxed">
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" />
                  <span>We work alongside our clients, not above them</span>
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" />
                  <span>Collaborative approach focused on sustainable trade growth</span>
                </p>
                <p className="flex items-start gap-2">
                  <CheckCircle2 className="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5" />
                  <span>Building long-term trust and partnerships</span>
                </p>
              </div>
            </Card>
          </div>
        </div>
      </div>

      {/* Our Commitment Section */}
      <div className="relative max-w-6xl mx-auto px-4 sm:px-6 py-16">
        <Card className="bg-gradient-to-br from-blue-900/40 to-purple-900/40 backdrop-blur-sm border-blue-700/50 p-10 sm:p-16 text-center">
          <h2 className="text-2xl sm:text-4xl font-bold text-white mb-6">Our Commitment</h2>
          <div className="w-20 h-1 bg-gradient-to-r from-blue-400 to-purple-400 mx-auto rounded-full mb-8" />
          <p className="text-lg sm:text-xl text-slate-200 leading-relaxed max-w-3xl mx-auto mb-4">
            <span className="font-semibold text-white">We simplify trade finance.</span>
          </p>
          <p className="text-base sm:text-lg text-slate-300 leading-relaxed max-w-3xl mx-auto">
            By purchasing receivables, we convert pending payments into working capital — allowing businesses to operate with confidence, flexibility, and stability.
          </p>
        </Card>
      </div>

      {/* Footer spacing */}
      <div className="h-16" />
    </div>
  );
}