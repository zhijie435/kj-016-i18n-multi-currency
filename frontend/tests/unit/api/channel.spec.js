import request from '@/utils/request'
import {
  fetchChannels,
  fetchEnabledChannels,
  fetchChannel,
  createChannel,
  updateChannel,
  updateChannelLocale,
  deleteChannel,
  fetchChannelLocale,
  fetchChannelCurrency
} from '@/api/channel'

jest.mock('@/utils/request')

describe('channel API', () => {
  beforeEach(() => {
    jest.clearAllMocks()
  })

  it('fetchChannels should GET /channels', async () => {
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchChannels()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/channels')
    expect(result).toEqual(mockResponse)
  })

  it('fetchEnabledChannels should GET /channels/enabled', async () => {
    const mockResponse = { data: [] }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchEnabledChannels()

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith('/channels/enabled')
    expect(result).toEqual(mockResponse)
  })

  it('fetchChannel should GET /channels/:id', async () => {
    const id = 1
    const mockResponse = { data: { id, code: 'default' } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchChannel(id)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith(`/channels/${id}`)
    expect(result).toEqual(mockResponse)
  })

  it('createChannel should POST /channels with data', async () => {
    const data = { code: 'test_channel', name: 'Test Channel', enabled: true }
    const mockResponse = { data: { id: 1, ...data } }
    request.post.mockResolvedValue(mockResponse)

    const result = await createChannel(data)

    expect(request.post).toHaveBeenCalledTimes(1)
    expect(request.post).toHaveBeenCalledWith('/channels', data)
    expect(result).toEqual(mockResponse)
  })

  it('updateChannel should PUT /channels/:id with data', async () => {
    const id = 1
    const data = { name: 'Updated Channel' }
    const mockResponse = { data: { id, ...data } }
    request.put.mockResolvedValue(mockResponse)

    const result = await updateChannel(id, data)

    expect(request.put).toHaveBeenCalledTimes(1)
    expect(request.put).toHaveBeenCalledWith(`/channels/${id}`, data)
    expect(result).toEqual(mockResponse)
  })

  it('updateChannelLocale should PUT /channels/:id/locale with locale_code', async () => {
    const id = 1
    const localeCode = 'en'
    const mockResponse = { data: { success: true } }
    request.put.mockResolvedValue(mockResponse)

    const result = await updateChannelLocale(id, localeCode)

    expect(request.put).toHaveBeenCalledTimes(1)
    expect(request.put).toHaveBeenCalledWith(`/channels/${id}/locale`, { locale_code: localeCode })
    expect(result).toEqual(mockResponse)
  })

  it('deleteChannel should DELETE /channels/:id', async () => {
    const id = 1
    const mockResponse = { data: { success: true } }
    request.delete.mockResolvedValue(mockResponse)

    const result = await deleteChannel(id)

    expect(request.delete).toHaveBeenCalledTimes(1)
    expect(request.delete).toHaveBeenCalledWith(`/channels/${id}`)
    expect(result).toEqual(mockResponse)
  })

  it('fetchChannelLocale should GET /channels/:channelCode/locale', async () => {
    const channelCode = 'default'
    const mockResponse = { data: { locale: 'zh_CN' } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchChannelLocale(channelCode)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith(`/channels/${channelCode}/locale`)
    expect(result).toEqual(mockResponse)
  })

  it('fetchChannelCurrency should GET /channels/:channelCode/currency', async () => {
    const channelCode = 'default'
    const mockResponse = { data: { code: 'CNY', symbol: '¥' } }
    request.get.mockResolvedValue(mockResponse)

    const result = await fetchChannelCurrency(channelCode)

    expect(request.get).toHaveBeenCalledTimes(1)
    expect(request.get).toHaveBeenCalledWith(`/channels/${channelCode}/currency`)
    expect(result).toEqual(mockResponse)
  })
})
