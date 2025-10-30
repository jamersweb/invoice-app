<template>
  <footer class="mt-12 border-t border-gray-200 bg-white">
    <div class="mx-auto max-w-7xl px-6 py-8 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div>
          <div class="text-base font-semibold text-gray-900">InvoiceApp</div>
          <p class="mt-2 text-sm text-gray-600">Empowering businesses with faster cash flow.</p>
        </div>
        <div class="text-sm text-gray-600">
          <div class="font-medium text-gray-900">Company</div>
          <ul class="mt-2 space-y-1">
            <li><a href="/how-it-works" class="hover:underline">How it works</a></li>
            <li><a href="/faqs" class="hover:underline">FAQs</a></li>
            <li><a href="/contact" class="hover:underline">Contact</a></li>
          </ul>
        </div>
        <div class="text-sm text-gray-600">
          <div class="font-medium text-gray-900">Legal</div>
          <ul class="mt-2 space-y-1">
            <li><a href="#" class="hover:underline">Privacy</a></li>
            <li><a href="#" class="hover:underline">Terms</a></li>
          </ul>
        </div>
      </div>
      <div class="mt-8 text-xs text-gray-500">© {{ new Date().getFullYear() }} InvoiceApp. All rights reserved.</div>
    </div>
  </footer>
</template>






