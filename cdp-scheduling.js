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
      let full_label = document.getElementById("full_".concat(shift_id));
      let join_button = document.getElementById("join_".concat(shift_id));
      let joining_button = document.getElementById("joining_".concat(shift_id));
      join_button.hidden = true
      joining_button.hidden = false

      jQuery.ajax({
        type: "POST",
        url: cdpAjax.ajaxURL,
        dataType: "json",
        data: {
          action: "cdp_join_shift",
          shift_id: shift_id,
          name: name,
          phone: phone,
          nonce: nonce
        },
        success: function(response) {
          joining_button.hidden = true
          if (response.type == "success") {
            full_label.hidden = false
            let updated_name = response.name
            let joiner_id = "#joiner_".concat(shift_id)
            let joiner_container = document.getElementById("shift_joiner_".concat(shift_id))
            jQuery(joiner_id).html(updated_name)
            joiner_container.hidden = false
          } else {
            join_button.hidden = false
            console.log(response.error_reason)
            alert(response.error_reason)
          }
        },
        error: function(error) {
          join_button.hidden = false
          joining_button.hidden = true
          alert(error.responseText)
          console.log(error);
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
      let create_button = document.getElementById("create_".concat(day_offset));
      let creating_button = document.getElementById("creating_".concat(day_offset));
      create_button.hidden = true
      creating_button.hidden = false

      jQuery.ajax({
        type: "POST",
        url: cdpAjax.ajaxURL,
        dataType: "json",
        data: {
          action: "cdp_create_shift",
          date: date,
          day_id: day_offset,
          name: name,
          phone: phone,
          start_time: start_time,
          end_time: end_time,
          location: location_name,
          capacity: capacity,
          notes: notes,
          nonce: nonce
        },
        success: function(response) {
          create_button.hidden = false
          creating_button.hidden = true
          if (response.type == "success") {
            alert("success")
          } else {
            alert(response.error_reason)
          }
        },
        error: function(error) {
          create_button.hidden = false
          creating_button.hidden = true
          alert(error.responseText)
          console.log(error);
        }
      })
    }
  })
})
