<template>
  <div class=" bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 relative overflow-hidden"
    bis_skin_checked="1">
    <div class="absolute inset-0 overflow-hidden" bis_skin_checked="1">
      <div class="absolute inset-0 bg-gradient-to-br from-blue-600/5 via-purple-600/5 to-emerald-600/5"
        bis_skin_checked="1"></div>
      <div class="absolute top-0 left-1/4 w-96 h-96 bg-blue-500/10 rounded-full blur-3xl animate-pulse"
        bis_skin_checked="1"></div>
      <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl animate-pulse"
        style="animation-delay: 1s;" bis_skin_checked="1"></div>
    </div>
    <footer class="relative">
      <div class="mx-auto max-w-7xl px-6 py-8 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
          <div>
            <div class="text-base font-semibold ">InvoiceApp</div>
            <p class="mt-2 text-sm  ">Empowering businesses with faster cash flow.</p>
          </div>
          <div class="text-sm  ">
            <div class="font-medium ">Company</div>
            <ul class="mt-2 space-y-1">
              <li><a href="/how-it-works" class="hover:underline">How it works</a></li>
              <li><a href="/faqs" class="hover:underline">FAQs</a></li>
              <li><a href="/contact" class="hover:underline">Contact</a></li>
            </ul>
          </div>
          <div class="text-sm  ">
            <div class="font-medium ">Legal</div>
            <ul class="mt-2 space-y-1">
              <li><a href="#" class="hover:underline">Privacy</a></li>
              <li><a href="#" class="hover:underline">Terms</a></li>
            </ul>
          </div>
        </div>
        <div class="mt-8 text-xs text-gray-500">© {{ new Date().getFullYear() }} InvoiceApp. All rights reserved.</div>
      </div>
    </footer>
  </div>

</template>
