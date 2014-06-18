<?php
class GCE_Event{
	private $id;
	private $title;
	private $description;
	private $location;
	private $start_time;
	private $end_time;
	private $link;
	private $type;
	private $num_in_day;
	private $pos;
	private $feed;
	private $day_type;
	private $time_now;
	private $regex;

	function __construct( $id, $title, $description, $location, $start_time, $end_time, $link ) {
		$this->id = $id;
		$this->title = $title;
		$this->description = $description;
		$this->location = $location;
		$this->start_time = $start_time;
		$this->end_time = $end_time;
		$this->link = $link;

		//Calculate which day type this event is (SWD = single whole day, SPD = single part day, MWD = multiple whole day, MPD = multiple part day)
		if ( ( $start_time + 86400 ) <= $end_time ) {
			if ( ( $start_time + 86400 ) == $end_time ) {
				$this->day_type = 'SWD';
			} else {
				if ( ( '12:00 am' == date( 'g:i a', $start_time ) ) && ( '12:00 am' == date( 'g:i a', $end_time ) ) ) {
					$this->day_type = 'MWD';
				} else {
					$this->day_type = 'MPD';
				}
			}
		} else {
			$this->day_type = 'SPD';
		}
	}

	function get_start_time() {
		return $this->start_time;
	}

	function get_end_time() {
		return $this->end_time;
	}

	function get_day_type() {
		return $this->day_type;
	}

	//Returns an array of days (as UNIX timestamps) that this events spans
	function get_days() {
		//Round start date to nearest day
		$start_time = mktime( 0, 0, 0, date( 'm', $this->start_time ), date( 'd', $this->start_time ) , date( 'Y', $this->start_time ) );

		$days = array();

		//If multiple day events should be handled, and this event is a multi-day event, add multiple day event to required days
		if ( $this->feed->get_multi_day() && ( 'MPD' == $this->day_type || 'MWD' == $this->day_type ) ) {
			$on_next_day = true;
			$next_day = $start_time;

			while ( $on_next_day ) {
				//If the end time of the event is after 00:00 on the next day (therefore, not doesn't end on this day)
				if ( $this->end_time > $next_day ) {
					//If $next_day is within the event retrieval date range (specified by retrieve events from / until settings)
					if ( $next_day >= $this->feed->get_feed_start() && $next_day < $this->feed->get_feed_end() ) {
						$days[] = $next_day;
					}
				} else {
					$on_next_day = false;
				}
				$next_day += 86400;
			}
		} else {
			//Add event into array of events for that day
			$days[] = $start_time;
		}

		return $days;
	}

	//Returns the markup for this event, so that it can be used in the construction of a grid / list
	function get_event_markup( $display_type, $num_in_day, $num ) {
		//Set the display type (either tooltip or list)
		$this->type = $display_type;

		//Set which number event this is in day (first in day etc)
		$this->num_in_day = $num_in_day;

		//Set the position of this event in array of events currently being processed
		$this->pos = $num;

		$this->time_now = current_time( 'timestamp' );

		//Use the builder or the old display options to create the markup, depending on user choice
		//if ( $this->feed->get_use_builder() )
		//	return $this->use_builder();

		return $this->use_old_display_options();
	}

	//Returns the difference between two times in human-readable format. Based on a patch for human_time_diff posted in the WordPress trac (http://core.trac.wordpress.org/ticket/9272) by Viper007Bond 
	function gce_human_time_diff( $from, $to = '', $limit = 1 ) {
		$units = array(
			31556926 => array( __( '%s year', GCE_TEXT_DOMAIN ),  __( '%s years', GCE_TEXT_DOMAIN ) ),
			2629744  => array( __( '%s month', GCE_TEXT_DOMAIN ), __( '%s months', GCE_TEXT_DOMAIN ) ),
			604800   => array( __( '%s week', GCE_TEXT_DOMAIN ),  __( '%s weeks', GCE_TEXT_DOMAIN ) ),
			86400    => array( __( '%s day', GCE_TEXT_DOMAIN ),   __( '%s days', GCE_TEXT_DOMAIN ) ),
			3600     => array( __( '%s hour', GCE_TEXT_DOMAIN ),  __( '%s hours', GCE_TEXT_DOMAIN ) ),
			60       => array( __( '%s min', GCE_TEXT_DOMAIN ),   __( '%s mins', GCE_TEXT_DOMAIN ) ),
		);

		if ( empty( $to ) )
			$to = time(); 

		$from = (int) $from;
		$to   = (int) $to;
		$diff = (int) abs( $to - $from );

		$items = 0;
		$output = array();

		foreach ( $units as $unitsec => $unitnames ) {
			if ( $items >= $limit )
				break; 

			if ( $diff < $unitsec )
				continue; 

			$numthisunits = floor( $diff / $unitsec ); 
			$diff = $diff - ( $numthisunits * $unitsec ); 
			$items++; 

			if ( $numthisunits > 0 )
				$output[] = sprintf( _n( $unitnames[0], $unitnames[1], $numthisunits ), $numthisunits ); 
		} 

		$seperator = _x( ', ', 'human_time_diff' ); 

		if ( ! empty( $output ) ) {
			return implode( $seperator, $output ); 
		} else {
			$smallest = array_pop( $units ); 
			return sprintf( $smallest[0], 1 ); 
		} 
	} 
}
?>