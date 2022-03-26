jQuery(document).ready( function() {
  jQuery(".join").click( function(event) {
    event.preventDefault();

    nonce = jQuery(this).attr("data-nonce")
    shift_id = jQuery(this).attr("data-shift_id")
    
    let name_field = document.getElementById('contact_name');
    let phone_field = document.getElementById('contact_phone');

    let name = name_field.value;
    let phone = phone_field.value;

    // required fields
    if (name === "") {
      name_field.select();
      name_field.scrollIntoView({block: "center"});
      alert("Please enter your name.");
    } else if (phone === "") {
      phone_field.select();
      phone_field.scrollIntoView({block: "center"});
      alert("Please enter your phone number in case a volunteer needs to contact you.");
    } else {
      jQuery.ajax({
        type: "POST",
        dataType: "json",
        url: "cdp-join-shift.php",
        data: { action: "cdp_user_join_shift",
                shift_id: shift_id,
                name: name,
                phone: phone,
                nonce: nonce },
        success: function(response) {
          if (response.type == "success") {
            alert("success")
          } else {
            alert(response.error_reason)
          }
        }
      })
    }
  })

  jQuery(".create").click( function(event) {
    event.preventDefault();

    nonce = jQuery(this).attr("data-nonce")
    date = jQuery(this).attr("data-date")
    day_offset = jQuery(this).attr("data-day_id")

    let name_field = document.getElementById('contact_name');
    let phone_field = document.getElementById('contact_phone');
    let start_time_field = document.getElementById('start_time_'.concat(day_offset));
    let end_time_field = document.getElementById('end_time_'.concat(day_offset));
    let location_selector = document.getElementById('create_location_'.concat(day_offset));
    let capacity_field = document.getElementById('capacity_'.concat(day_offset));
    let notes_field = document.getElementById('notes_'.concat(day_offset));

    let name = name_field.value;
    let phone = phone_field.value;
    let start_time = start_time_field.value;
    let end_time = end_time_field.value;
    let location_name = location_selector.value;
    let capacity = capacity_field.value;
    let notes = notes_field.value;

    // required fields
    if (name === "") {
      name_field.select();
      name_field.scrollIntoView({block: "center"});
      alert("Please enter your name.");
    } else if (phone === "") {
      phone_field.select();
      phone_field.scrollIntoView({block: "center"});
      alert("Please enter your phone number in case a volunteer needs to contact you.");
    } else if (start_time === "") {
      start_time_field.select();
      start_time_field.scrollIntoView({block: "center"});
      alert("Please enter a start time.");
    } else if (end_time === "") {
      end_time_field.select();
      end_time_field.scrollIntoView({block: "center"});
      alert("Please enter an end time.");
    } else if (location_name === "" || location_name == "none") {
      location_selector.scrollIntoView({block: "center"});
      alert("Please enter a location.");
    } else {
      jQuery.ajax({
        type: "POST",
        dataType: "json",
        url: "cdp-create-shift.php",
        data: { action: "cdp_user_create_shift",
                date: date,
                day_id: day_offset,
                name: name,
                phone: phone,
                start_time: start_time,
                end_time: end_time,
                location: location_name,
                capacity: capacity,
                notes: notes,
                nonce: nonce },
        success: function(response) {
          if (response.type == "success") {
            alert("success")
          } else {
            alert(response.error_reason)
          }
        }
      })
    }
  })
})
