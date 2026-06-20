<?php $pageTitle = 'Contact the Director General'; include_once 'shared/_header.php'; ?>
<style>
.dept-contact { max-width: 800px; margin: 40px auto; padding: 0 20px; }
.dept-card { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); padding: 32px; text-align: center; }
.dept-card .dept-icon { width: 72px; height: 72px; border-radius: 50%; background: linear-gradient(135deg,#e2b714,#d97706); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 30px; margin: 0 auto 16px; }
.dept-card h2 { font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 6px; }
.dept-card .dept-subtitle { color: #64748b; font-size: 14px; margin-bottom: 24px; }
.contact-btn-group { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; margin-bottom: 24px; }
.contact-btn { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; border-radius: 12px; font-size: 15px; font-weight: 600; text-decoration: none; transition: all 0.2s ease; border: none; cursor: pointer; }
.contact-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0,0,0,0.12); }
.contact-btn.email { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }
.contact-btn.email:hover { background: #2563eb; color: #fff; }
.contact-btn.whatsapp { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.contact-btn.whatsapp:hover { background: #059669; color: #fff; }
.contact-btn.call { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.contact-btn.call:hover { background: #dc2626; color: #fff; }
.dept-details { text-align: left; margin-top: 24px; padding: 20px; background: #f8fafc; border-radius: 12px; }
.dept-details h4 { font-size: 15px; font-weight: 600; color: #0f172a; margin-bottom: 12px; }
.dept-details p { font-size: 13px; color: #475569; line-height: 1.7; margin-bottom: 8px; }
.dept-details i { width: 20px; color: #e2b714; }
.back-link { display: inline-flex; align-items: center; gap: 6px; color: #64748b; text-decoration: none; font-size: 13px; margin-bottom: 20px; transition: color 0.2s; }
.back-link:hover { color: #e2b714; }
@media (max-width: 480px) { .dept-card { padding: 20px; } .contact-btn { width: 100%; justify-content: center; } }
</style>
<div class="dept-contact">
<a href="contact.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Contact</a>
<div class="dept-card">
<div class="dept-icon"><i class="fas fa-crown"></i></div>
<h2>Director General</h2>
<p class="dept-subtitle">Executive leadership, institutional strategy, and high-level communications</p>
<div class="contact-btn-group">
<a href="mailto:director@igangaschoolofnursingandmidwifery.ac.ug" class="contact-btn email"><i class="fas fa-envelope"></i> director@igangaschool...</a>
<a href="https://wa.me/256772514889?text=Hello%20Director%20General" target="_blank" class="contact-btn whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp Director</a>
<a href="tel:+256772514889" class="contact-btn call"><i class="fas fa-phone"></i> Call Director</a>
</div>
<div class="dept-details">
<h4><i class="fas fa-info-circle me-2"></i>About the Director General</h4>
<p><i class="fas fa-check-circle me-2"></i> The Director General provides strategic leadership and oversees all institution operations. For partnership proposals, policy matters, and executive communications.</p>
<p><i class="fas fa-clock me-2"></i> Appointment required. Office Hours: Mon-Fri, 9:00 AM - 4:00 PM</p>
<p><i class="fas fa-map-marker-alt me-2"></i> Located at Executive Office, Administration Block, ISNM Main Campus</p>
</div>
</div>
</div>
<?php include_once 'shared/_footer.php'; ?>
