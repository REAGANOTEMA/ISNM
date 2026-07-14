<?php
$uqid = 'sqs_' . substr(md5(uniqid()), 0, 6);
?>
<div class="position-fixed d-print-none" style="bottom:var(--fab-search,144px);right:24px;z-index:9999">
    <button class="btn btn-primary rounded-circle shadow-lg" id="<?= $uqid ?>_toggle"
            style="width:56px;height:56px;" title="Search Students (Ctrl+K)"
            onclick="document.getElementById('<?= $uqid ?>_modal').classList.add('show');document.getElementById('<?= $uqid ?>_modal').style.display='block';document.getElementById('<?= $uqid ?>_input').focus()">
        <i class="fas fa-search"></i>
    </button>
</div>

<div class="modal fade" id="<?= $uqid ?>_modal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header bg-dark text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-search me-2"></i>Quick Student Search</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom" style="background:#f8fafc;">
                    <div class="input-group input-group-lg">
                        <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control border-start-0 ps-0" id="<?= $uqid ?>_input"
                               placeholder="Type name, ID, or phone number..." autocomplete="off"
                               onkeyup="sqs_search('<?= $uqid ?>')" onkeydown="if(event.key==='Escape')sqs_close('<?= $uqid ?>')">
                        <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('<?= $uqid ?>_input').value='';sqs_search('<?= $uqid ?>')">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="mt-2 small text-muted">
                        <kbd class="bg-light text-dark border px-1 py-0">Ctrl+K</kbd> to open &middot;
                        <kbd class="bg-light text-dark border px-1 py-0">Esc</kbd> to close
                    </div>
                </div>
                <div id="<?= $uqid ?>_results" class="p-0" style="max-height:60vh;overflow-y:auto;">
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-user-graduate fa-3x mb-3" style="opacity:.3"></i>
                        <p class="fs-6">Start typing to search students</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                <small class="text-muted"><i class="fas fa-database me-1"></i>Students Database</small>
                <small class="text-muted" id="<?= $uqid ?>_count"></small>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="<?= $uqid ?>_profile_modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-graduate me-2"></i>Student Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="<?= $uqid ?>_profile_body">
                <div class="text-center py-5 text-muted"><i class="fas fa-spinner fa-spin fa-2x"></i><p class="mt-2">Loading...</p></div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Profile</button>
            </div>
        </div>
    </div>
</div>

<style>
#<?= $uqid ?>_modal .list-group-item-action { cursor:pointer; border-left:3px solid transparent; transition:all .15s; }
#<?= $uqid ?>_modal .list-group-item-action:hover, #<?= $uqid ?>_modal .list-group-item-action.highlight { background:#f0f7ff; border-left-color:#0d6efd; }
#<?= $uqid ?>_toggle { transition:transform .2s, box-shadow .2s; }
#<?= $uqid ?>_toggle:hover { transform:scale(1.1); box-shadow:0 8px 25px rgba(13,110,253,.4); }
#<?= $uqid ?>_profile_modal .profile-detail-table td { padding:6px 8px; }
#<?= $uqid ?>_profile_modal .profile-detail-table tr:nth-child(even) { background:#f8fafc; }
@media print { #<?= $uqid ?>_toggle, #<?= $uqid ?>_modal, #<?= $uqid ?>_profile_modal .modal-footer, #<?= $uqid ?>_profile_modal .btn-close, #<?= $uqid ?>_profile_modal .modal-header { display:none !important; } }
</style>

<script>
(function(){
    var uid = '<?= $uqid ?>';
    var input = document.getElementById(uid+'_input');
    var results = document.getElementById(uid+'_results');
    var modal = document.getElementById(uid+'_modal');
    var profileModal = document.getElementById(uid+'_profile_modal');
    var profileBody = document.getElementById(uid+'_profile_body');
    var searchTimer = null;
    var selectedIndex = -1;

    window['sqs_search'] = function(id){
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function(){ doSearch(id); }, 300);
    };

    function doSearch(id){
        var term = document.getElementById(id+'_input').value.trim();
        if(term.length < 1){
            results.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-user-graduate fa-3x mb-3" style="opacity:.3"></i><p class="fs-6">Start typing to search students</p></div>';
            document.getElementById(id+'_count').textContent = '';
            return;
        }
        results.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Searching...</p></div>';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../includes/ajax_student_search.php?term='+encodeURIComponent(term)+'&limit=20', true);
        xhr.onload = function(){
            if(xhr.status === 200){
                try {
                    var data = JSON.parse(xhr.responseText);
                    renderResults(id, data);
                } catch(e){
                    results.innerHTML = '<div class="alert alert-danger m-3">Error parsing results</div>';
                }
            } else {
                results.innerHTML = '<div class="alert alert-danger m-3">Search failed</div>';
            }
        };
        xhr.onerror = function(){ results.innerHTML = '<div class="alert alert-danger m-3">Search failed (network)</div>'; };
        xhr.send();
    }

    function renderResults(id, data){
        var div = document.getElementById(id+'_results');
        var countEl = document.getElementById(id+'_count');
        if(!data.success || !data.students || data.students.length === 0){
            div.innerHTML = '<div class="text-center py-5 text-muted"><i class="fas fa-search fa-2x mb-2" style="opacity:.3"></i><p>No students found</p></div>';
            countEl.textContent = '';
            return;
        }
        countEl.textContent = data.count + ' result' + (data.count !== 1 ? 's' : '');
        var html = '<div class="list-group list-group-flush">';
        data.students.forEach(function(s, i){
            var name = s.full_name || (s.first_name + ' ' + (s.other_name||'') + ' ' + s.surname).trim() || 'Unknown';
            var sid = s.student_id || s.student_number || s.index_number || '-';
            var prog = s.program || s.level || '-';
            var phone = s.phone || s.mobile_number || '';
            var hasPhoto = (s.passport_photo || s.profile_picture);
            var initials = (s.first_name ? s.first_name[0] : 'S') + (s.surname ? s.surname[0] : 'T');
            html += '<div class="list-group-item list-group-item-action d-flex align-items-center gap-2 p-2" data-index="'+i+'" onclick="sqs_viewProfile(\''+id+'\',\''+escapeJs(sid)+'\')">';
            if(hasPhoto){
                html += '<img src="'+escapeHtml(hasPhoto)+'" alt="" style="width:40px;height:40px;border-radius:50%;object-fit:cover;flex-shrink:0;">';
            } else {
                html += '<div style="width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">'+initials+'</div>';
            }
            html += '<div class="flex-grow-1 min-w-0"><div class="fw-semibold text-truncate small">'+escapeHtml(name)+'</div>';
            html += '<small class="text-muted" style="font-size:11px;"><code>'+escapeHtml(sid)+'</code> &middot; '+escapeHtml(prog)+'</small></div>';
            // Action buttons
            html += '<div class="btn-group btn-group-xs flex-shrink-0" style="white-space:nowrap">';
            html += '<a href="../print_certificate.php?student_id='+encodeURIComponent(sid)+'&print=1" target="_blank" class="btn btn-sm btn-outline-info" title="Print Certificate" onclick="event.stopPropagation()"><i class="fas fa-certificate"></i></a>';
            html += '<a href="../print_transcript.php?action=print&student_id='+encodeURIComponent(sid)+'" target="_blank" class="btn btn-sm btn-outline-success" title="Print Transcript" onclick="event.stopPropagation()"><i class="fas fa-scroll"></i></a>';
            html += '<button class="btn btn-sm btn-outline-secondary" title="View Profile" onclick="event.stopPropagation();sqs_viewProfile(\''+id+'\',\''+escapeJs(sid)+'\')"><i class="fas fa-eye"></i></button>';
            html += '</div>';
            html += '<span class="badge bg-'+(s.status === 'Active' ? 'success' : 'secondary')+' flex-shrink-0" style="font-size:10px;">'+(s.status||'Active')+'</span>';
            html += '</div>';
        });
        html += '</div>';
        div.innerHTML = html;
        selectedIndex = -1;
    }

    window['sqs_viewProfile'] = function(id, studentId){
        var modal = document.getElementById(id+'_profile_modal');
        var body = document.getElementById(id+'_profile_body');
        body.innerHTML = '<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x text-muted"></i><p class="text-muted mt-2">Loading profile...</p></div>';
        var bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../includes/ajax_student_profile.php?student_id='+encodeURIComponent(studentId), true);
        xhr.onload = function(){
            if(xhr.status === 200){
                body.innerHTML = xhr.responseText;
            } else {
                body.innerHTML = '<div class="alert alert-danger m-3">Failed to load profile</div>';
            }
        };
        xhr.onerror = function(){ body.innerHTML = '<div class="alert alert-danger m-3">Failed to load profile (network)</div>'; };
        xhr.send();
    };

    window['sqs_close'] = function(id){
        var m = new bootstrap.Modal(document.getElementById(id+'_modal'));
        m.hide();
    };

    document.addEventListener('keydown', function(e){
        if((e.ctrlKey || e.metaKey) && e.key === 'k'){
            e.preventDefault();
            // If global search is available, use it instead of the mini search
            if (typeof openGlobalSearch === 'function') {
                openGlobalSearch();
                return;
            }
            document.getElementById(uid+'_toggle').click();
        }
        if(e.key === 'Escape'){
            var pm = document.getElementById(uid+'_profile_modal');
            if(pm && pm.classList.contains('show')){
                var bm = bootstrap.Modal.getInstance(pm);
                if(bm) bm.hide();
            }
        }
    });

    function escapeHtml(t){ if(!t)return ''; var d=document.createElement('div'); d.textContent=t; return d.innerHTML; }
    window['escapeJs'] = function(s){ if(!s)return ''; return String(s).replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;'); };

    input.addEventListener('keydown', function(e){
        var items = results.querySelectorAll('.list-group-item-action');
        if(items.length === 0) return;
        if(e.key === 'ArrowDown'){ e.preventDefault(); selectedIndex = Math.min(selectedIndex+1, items.length-1); highlightItem(items); }
        else if(e.key === 'ArrowUp'){ e.preventDefault(); selectedIndex = Math.max(selectedIndex-1, -1); highlightItem(items); }
        else if(e.key === 'Enter' && selectedIndex >= 0 && selectedIndex < items.length){
            e.preventDefault(); items[selectedIndex].click();
        }
    });

    function highlightItem(items){
        items.forEach(function(item, i){
            item.classList.toggle('highlight', i === selectedIndex);
            if(i === selectedIndex) item.scrollIntoView({block:'nearest'});
        });
    }
})();
</script>
<?php $studentQuickSearchRendered = true; ?>
