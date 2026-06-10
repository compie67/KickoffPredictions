(function($){
  if (!$) return;
  var timers = {};
  function tokenData(){
    var data = {};
    $('.kickoff-token-source input').each(function(){
      data[$(this).attr('name')] = $(this).val();
    });
    return data;
  }
  function postAction(url, payload, cb, fail){
    if (typeof Ossn !== 'undefined' && typeof Ossn.PostRequest === 'function') {
      Ossn.PostRequest({
        url: url,
        action: false,
        params: payload,
        callback: function(callback){
          var resp = callback;
          if (typeof callback === 'string') {
            try { resp = JSON.parse(callback); } catch(e) { resp = {ok:false, message: callback}; }
          }
          cb(resp);
        }
      });
      return;
    }
    $.post(url, payload, cb, 'json').fail(fail);
  }
  function saveMatch(matchId){
    var wrap = $('[data-kickoff-tournament]');
    if (!wrap.length || typeof Ossn === 'undefined') return;
    var tournament = wrap.data('kickoff-tournament');
    var type = wrap.data('kickoff-type') || 'score';
    var status = $('[data-status-for="'+matchId+'"]');
    var payload = tokenData();
    payload.tournament = tournament;
    payload.match_id = matchId;

    if (type === 'pick_winner') {
      var winner = $('.kickoff-winner-pick[data-match="'+matchId+'"]').val();
      if (winner === '') return;
      payload.winner_id = winner;
    } else {
      var home = $('.kickoff-score[data-match="'+matchId+'"][data-side="home"]').val();
      var away = $('.kickoff-score[data-match="'+matchId+'"][data-side="away"]').val();
      if (home === '' || away === '') return;
      payload.home_score = home;
      payload.away_score = away;
    }

    status.text('...');
    postAction(Ossn.site_url + 'action/kickoff/prediction/save', payload, function(resp){
      if (resp && resp.message) status.text(resp.message);
      if (resp && !resp.ok) status.addClass('kickoff-error'); else status.removeClass('kickoff-error');
    }, function(){
      status.text('Opslaan mislukt').addClass('kickoff-error');
    });
  }
  $(document).on('input change', '.kickoff-score', function(){
    var matchId = $(this).data('match');
    clearTimeout(timers[matchId]);
    timers[matchId] = setTimeout(function(){ saveMatch(matchId); }, 500);
  });
  $(document).on('change', '.kickoff-winner-pick', function(){
    saveMatch($(this).data('match'));
  });
  $(document).on('change', '.kickoff-tournament-switch', function(){
    var url = $(this).val();
    if (url) window.location.href = url;
  });

  $(document).on('click', '.kickoff-filter', function(){
    var filter = $(this).data('filter');
    $('.kickoff-filter').removeClass('active');
    $(this).addClass('active');
    $('.kickoff-match').each(function(){
      var show = filter === 'all' || (filter === 'open' && $(this).data('locked') != 1) || (filter === 'locked' && $(this).data('locked') == 1) || (filter === 'filled' && $(this).data('filled') == 1);
      $(this).toggleClass('kickoff-hidden', !show);
    });
  });
  function applyAdminFilters(){
    var group = $('.kickoff-admin-filter[data-filter="group"]').val() || '';
    var date = $('.kickoff-admin-filter[data-filter="date"]').val() || '';
    var status = $('.kickoff-admin-filter[data-filter="status"]').val() || '';
    $('.kickoff-result-form').each(function(){
      var show = true;
      if (group && $(this).data('group') !== group) show = false;
      if (date && $(this).data('date') !== date) show = false;
      if (status && $(this).data('status') !== status) show = false;
      $(this).toggleClass('kickoff-hidden', !show);
    });
  }
  $(document).on('change', '.kickoff-admin-filter', applyAdminFilters);
})(window.jQuery);
