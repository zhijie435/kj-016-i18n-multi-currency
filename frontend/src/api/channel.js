import request from '@/utils/request'

export function fetchChannels() {
  return request.get('/channels')
}

export function fetchEnabledChannels() {
  return request.get('/channels/enabled')
}

export function fetchChannel(id) {
  return request.get(`/channels/${id}`)
}

export function createChannel(data) {
  return request.post('/channels', data)
}

export function updateChannel(id, data) {
  return request.put(`/channels/${id}`, data)
}

export function updateChannelLocale(id, localeCode) {
  return request.put(`/channels/${id}/locale`, { locale_code: localeCode })
}

export function deleteChannel(id) {
  return request.delete(`/channels/${id}`)
}

export function fetchChannelLocale(channelCode) {
  return request.get(`/channels/${channelCode}/locale`)
}
