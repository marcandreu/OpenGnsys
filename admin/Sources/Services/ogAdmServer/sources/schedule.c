#include "schedule.h"
#include "list.h"
#include <sys/types.h>
#include <stdint.h>
#include <stdlib.h>
#include <syslog.h>
#include <time.h>
#include <ev.h>

struct og_schedule *current_schedule = NULL;
static LIST_HEAD(schedule_list);

static void og_schedule_add(struct og_schedule *new)
{
	struct og_schedule *schedule, *next;

	list_for_each_entry_safe(schedule, next, &schedule_list, list) {
		if (new->seconds < schedule->seconds) {
			list_add(&new->list, &schedule->list);
			return;
		}
	}
	list_add_tail(&new->list, &schedule_list);
}

static void og_parse_years(uint16_t years_mask, int years[])
{
	int i, j = 0;

	for (i = 0; i < 16; i++) {
		if ((1 << i) & years_mask)
			years[j++] = 2009 + i - 1900;
	}
}

static void og_parse_months(uint16_t months_mask, int months[])
{
	int i, j = 0;

	for (i = 0; i < 12; i++) {
		if ((1 << i) & months_mask)
			months[j++] = i + 1;
	}
}

static void og_parse_days(uint16_t days_mask, int days[])
{
	int i, j = 0;

	for (i = 0; i < 31; i++) {
		if ((1 << i) & days_mask)
			days[j++] = i + 1;
	}
}

static void og_parse_hours(uint16_t hours_mask, uint8_t am_pm, int hours[])
{
	int pm = 12 * am_pm;
	int i, j = 0;

	for (i = 0; i < 12; i++) {
		if ((1 << i) & hours_mask)
			hours[j++] = i + pm;
	}
}
void og_schedule_create(unsigned int schedule_id, unsigned int task_id,
			struct og_schedule_time *time)
{
	struct og_schedule *schedule;
	int months[12] = {};
	int years[12] = {};
	int hours[12] = {};
	int days[31] = {};
	struct tm tm = {};
	int i, j, k = 0;
	int minutes;

	og_parse_years(time->years, years);
	og_parse_months(time->months, months);
	og_parse_days(time->days, days);
	og_parse_hours(time->hours, time->am_pm, hours);
	minutes = time->minutes;

	for (i = 0; years[i] != 0; i++) {
		for (j = 0; months[j] != 0; j++) {
			for (k = 0; days[k] != 0; k++) {
				schedule = (struct og_schedule *)
					calloc(1, sizeof(struct og_schedule));
				if (!schedule)
					return;

				memset(&tm, 0, sizeof(tm));
				tm.tm_year = years[i];
				tm.tm_mon = months[j] - 1;
				tm.tm_mday = days[k];
				tm.tm_hour = hours[k];
				tm.tm_min = minutes;

				schedule->seconds = mktime(&tm);
				schedule->task_id = task_id;
				schedule->schedule_id = schedule_id;
				og_schedule_add(schedule);
			}
		}
	}
}

void og_schedule_delete(struct ev_loop *loop, uint32_t schedule_id)
{
	struct og_schedule *schedule, *next;

	list_for_each_entry_safe(schedule, next, &schedule_list, list) {
		if (schedule->schedule_id != schedule_id)
			continue;

		list_del(&schedule->list);
		if (current_schedule == schedule) {
			ev_timer_stop(loop, &schedule->timer);
			current_schedule = NULL;
			og_schedule_update(loop);
		}
		free(schedule);
		break;
	}
}

static void og_agent_timer_cb(struct ev_loop *loop, ev_timer *timer, int events)
{
	struct og_schedule *current;

	current = container_of(timer, struct og_schedule, timer);
	og_dbi_schedule_task(current->task_id);

	ev_timer_stop(loop, timer);
	list_del(&current->list);
	free(current);

	og_schedule_next(loop);
}

void og_schedule_next(struct ev_loop *loop)
{
	struct og_schedule *schedule;
	time_t now, seconds;

	if (list_empty(&schedule_list)) {
		current_schedule = NULL;
		return;
	}

	schedule = list_first_entry(&schedule_list, struct og_schedule, list);
	now = time(NULL);
	if (schedule->seconds <= now)
		seconds = 0;
	else
		seconds = schedule->seconds - now;

	ev_timer_init(&schedule->timer, og_agent_timer_cb, seconds, 0.);
	ev_timer_start(loop, &schedule->timer);
	current_schedule = schedule;
}

void og_schedule_update(struct ev_loop *loop)
{
	if (current_schedule)
		ev_timer_stop(loop, &current_schedule->timer);

	og_schedule_next(loop);
}
