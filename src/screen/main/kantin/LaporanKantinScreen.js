import React, { useState, useEffect, useCallback } from 'react';
import {
  View, Text, StyleSheet, TouchableOpacity, useWindowDimensions,
  StatusBar, FlatList, ActivityIndicator, Alert, ScrollView, Image, RefreshControl, Platform
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect, useIsFocused } from '@react-navigation/native';
import { Ionicons, MaterialCommunityIcons, Feather } from '@expo/vector-icons';
import DateTimePicker from '@react-native-community/datetimepicker';
import apiClient from '../../../api/client';
import AsyncStorage from '@react-native-async-storage/async-storage';

const BLUE_PRIMARY = '#0B1E3F';
const WHITE = '#FFFFFF';
const TEXT_MAIN = '#1E293B';
const TEXT_MUTED = '#64748B';
const SUCCESS = '#10B981';

const formatDateLocal = (date) => {
  if (!date) return '';
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

export default function LaporanKantinScreen({ navigation }) {
  const { width } = useWindowDimensions();
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [errorOccurred, setErrorOccurred] = useState(false);
  const [userData, setUserData] = useState(null);
  const [stats, setStats] = useState({
    total_pts: 0,
    total_transaksi: 0,
    rata_rata: 0,
    transaksi_minggu_ini: 0
  });
  const [riwayat, setRiwayat] = useState([]);

  // DATE FILTER STATE
  const [startDate, setStartDate] = useState(new Date(new Date().getFullYear(), new Date().getMonth(), 1));
  const [endDate, setEndDate] = useState(new Date());
  const [showStartPicker, setShowStartPicker] = useState(false);
  const [showEndPicker, setShowEndPicker] = useState(false);

  useEffect(() => {
    loadUserData();
  }, []);

  const loadUserData = async () => {
    const dataStr = await AsyncStorage.getItem('user_data');
    if (dataStr) {
      const parsed = JSON.parse(dataStr);
      setUserData(parsed);
      fetchData(parsed.id, startDate, endDate);
    }
  };

  const fetchData = useCallback(async (kantinId, start = startDate, end = endDate) => {
    if (!kantinId) {
      setLoading(false);
      setRefreshing(false);
      return;
    }
    try {
      setErrorOccurred(false);
      const startStr = formatDateLocal(start);
      const endStr = formatDateLocal(end);
      const res = await apiClient.get(`kantin/kantin_get_laporan.php?kantin_id=${kantinId}&start_date=${startStr}&end_date=${endStr}`);
      if (res.data.status === 'success') {
        setStats(res.data.stats);
        setRiwayat(res.data.riwayat);
      }
    } catch (error) {
      console.error('Fetch Laporan Error:', error);
      setErrorOccurred(true);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  }, [startDate, endDate]);

  useFocusEffect(
    React.useCallback(() => {
      if (userData?.id) {
        fetchData(userData.id, startDate, endDate);
      }
    }, [userData?.id, startDate, endDate])
  );

  const isFocused = useIsFocused();

  useEffect(() => {
    if (!isFocused) return;
    const interval = setInterval(() => {
      if (userData?.id) fetchData(userData.id, startDate, endDate);
    }, 3000);
    return () => clearInterval(interval);
  }, [userData?.id, startDate, endDate, isFocused, fetchData]);

  const onRefresh = () => {
    setRefreshing(true);
    fetchData(userData?.id, startDate, endDate);
  };

  return (
    <View style={styles.container}>
      <StatusBar barStyle="light-content" translucent backgroundColor="transparent" />

      <View style={styles.header}>
        <Image
          source={require('../../../../assets/batik_cirebon.png')}
          style={styles.batikOverlay}
        />
        <SafeAreaView>
          <View style={styles.headerTop}>
            <Text style={styles.headerTitle}>Laporan Kantin</Text>
            <View style={styles.iconBtn}>
              <Feather name="bar-chart-2" size={22} color={WHITE} />
            </View>
          </View>

          <View style={styles.dateFilterContainer}>
            <TouchableOpacity
              style={styles.dateBtn}
              onPress={() => setShowStartPicker(true)}
            >
              <Text style={styles.dateLabel}>DARI TANGGAL</Text>
              <View style={styles.dateValRow}>
                <Ionicons name="calendar-outline" size={16} color={WHITE} />
                <Text style={styles.dateValText}>{startDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}</Text>
              </View>
            </TouchableOpacity>

            <View style={styles.dateArrow}>
              <Ionicons name="arrow-forward" size={16} color="rgba(255,255,255,0.4)" />
            </View>

            <TouchableOpacity
              style={styles.dateBtn}
              onPress={() => setShowEndPicker(true)}
            >
              <Text style={styles.dateLabel}>SAMPAI TANGGAL</Text>
              <View style={styles.dateValRow}>
                <Ionicons name="calendar-outline" size={16} color={WHITE} />
                <Text style={styles.dateValText}>{endDate.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' })}</Text>
              </View>
            </TouchableOpacity>
          </View>

          {showStartPicker && (
            <DateTimePicker
              value={startDate}
              mode="date"
              display={Platform.OS === 'ios' ? 'spinner' : 'default'}
              onChange={(e, date) => { setShowStartPicker(false); if (date) setStartDate(date); }}
            />
          )}

          {showEndPicker && (
            <DateTimePicker
              value={endDate}
              mode="date"
              display={Platform.OS === 'ios' ? 'spinner' : 'default'}
              onChange={(e, date) => { setShowEndPicker(false); if (date) setEndDate(date); }}
            />
          )}
        </SafeAreaView>
      </View>

      <View style={styles.whiteSection}>
        {loading ? (
          <View style={styles.loadingContainer}>
            <ActivityIndicator size="large" color={BLUE_PRIMARY} />
            <Text style={styles.loadingText}>Memuat Laporan Kantin...</Text>
          </View>
        ) : errorOccurred ? (
          <View style={styles.errorContainer}>
            <Ionicons name="cloud-offline-outline" size={56} color="#EF4444" />
            <Text style={styles.errorTitle}>Gagal Terhubung ke Server</Text>
            <Text style={styles.errorDesc}>
              Pastikan XAMPP sedang berjalan aktif di laptop Anda, dan IP server di client.js sudah sesuai.
            </Text>
            <TouchableOpacity style={styles.retryBtn} onPress={() => fetchData(userData?.id)}>
              <Text style={styles.retryBtnText}>Hubungkan Ulang</Text>
            </TouchableOpacity>
          </View>
        ) : (
          <ScrollView
            showsVerticalScrollIndicator={false}
            contentContainerStyle={{ paddingBottom: 120 }}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[BLUE_PRIMARY]} />}
          >

            <View style={styles.section}>
              <View style={styles.sectionHeader}>
                <Text style={styles.sectionTitle}>Riwayat Transaksi Terkini</Text>
                <View style={styles.realtimeBadge}>
                  <View style={styles.liveDot} />
                  <Text style={styles.realtimeText}>LIVE</Text>
                </View>
              </View>

              <View style={styles.listContainer}>
                {riwayat.length === 0 ? (
                  <View style={styles.emptyContainer}>
                    <MaterialCommunityIcons name="receipt-text-minus" size={48} color="#CBD5E1" />
                    <Text style={styles.emptyTitle}>Belum Ada Transaksi</Text>
                    <Text style={styles.emptyText}>Transaksi scan siswa akan muncul di sini secara realtime.</Text>
                  </View>
                ) : (
                  riwayat.map((item, idx) => (
                    <View key={`riwayat_${item.id}_${idx}`} style={styles.listItem}>
                      <View style={styles.listIconBox}>
                        <Ionicons name="person" size={20} color={BLUE_PRIMARY} />
                      </View>
                      <View style={styles.listBody}>
                        <Text style={styles.listTitle}>{item.nama}</Text>
                        <Text style={styles.listSub}>{item.sub}</Text>
                      </View>
                      <View style={styles.listTail}>
                        <Text style={[styles.listAmount, { color: SUCCESS }]}>+{item.amount} PTS</Text>
                      </View>
                    </View>
                  ))
                )}
              </View>
            </View>
          </ScrollView>
        )}
      </View>

      {/* BOTTOM NAV */}
      <View style={styles.bottomNav}>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('HomeKantin')}>
          <Ionicons name="grid-outline" size={24} color="#94A3B8" />
          <Text style={styles.navLabel}>Beranda</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.navItem}>
          <Ionicons name="bar-chart" size={24} color={BLUE_PRIMARY} />
          <Text style={[styles.navLabel, { color: BLUE_PRIMARY }]}>Laporan</Text>
        </TouchableOpacity>
        <TouchableOpacity style={styles.navItem} onPress={() => navigation.navigate('ProfilKantin')}>
          <Ionicons name="person-outline" size={24} color="#94A3B8" />
          <Text style={styles.navLabel}>Profil</Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: BLUE_PRIMARY },
  header: { paddingHorizontal: 25, paddingBottom: 60, paddingTop: 20, overflow: 'hidden' },
  batikOverlay: { ...StyleSheet.absoluteFillObject, opacity: 0.1, resizeMode: 'repeat' },
  headerTop: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 10 },
  headerTitle: { fontSize: 24, fontWeight: 'bold', color: WHITE },
  headerSubtitle: { fontSize: 12, color: 'rgba(255,255,255,0.6)', marginTop: 8 },
  iconBtn: { width: 44, height: 44, borderRadius: 12, backgroundColor: 'rgba(255,255,255,0.15)', justifyContent: 'center', alignItems: 'center' },

  dateFilterContainer: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 25,
    gap: 10
  },
  dateBtn: {
    flex: 1,
    backgroundColor: 'rgba(255,255,255,0.1)',
    padding: 12,
    borderRadius: 15,
    borderWidth: 1,
    borderColor: 'rgba(255,255,255,0.1)'
  },
  dateLabel: {
    fontSize: 9,
    color: 'rgba(255,255,255,0.5)',
    fontWeight: 'bold',
    marginBottom: 4
  },
  dateValRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 6
  },
  dateValText: {
    color: WHITE,
    fontSize: 14,
    fontWeight: 'bold'
  },
  dateArrow: {
    paddingTop: 15
  },

  whiteSection: { flex: 1, backgroundColor: '#F8FAFC', borderTopLeftRadius: 40, borderTopRightRadius: 40, paddingHorizontal: 25, paddingTop: 30, marginTop: -30 },

  loadingContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingTop: 80 },
  loadingText: { fontSize: 14, fontWeight: 'bold', color: '#94A3B8', marginTop: 15 },

  errorContainer: { flex: 1, justifyContent: 'center', alignItems: 'center', paddingHorizontal: 20, paddingTop: 60 },
  errorTitle: { fontSize: 18, fontWeight: '900', color: BLUE_PRIMARY, marginTop: 20 },
  errorDesc: { fontSize: 12, color: '#64748B', textAlign: 'center', marginTop: 10, lineHeight: 18 },
  retryBtn: { backgroundColor: BLUE_PRIMARY, paddingHorizontal: 25, paddingVertical: 12, borderRadius: 15, marginTop: 25, elevation: 5 },
  retryBtnText: { color: WHITE, fontSize: 14, fontWeight: 'bold' },

  metricsGrid: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'space-between', marginBottom: 30 },
  metricCard: { width: '48%', backgroundColor: WHITE, borderRadius: 24, padding: 18, marginBottom: 15, elevation: 4, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 10 },
  iconBox: { width: 40, height: 40, borderRadius: 12, justifyContent: 'center', alignItems: 'center', marginBottom: 12 },
  metricVal: { fontSize: 20, fontWeight: '900', color: BLUE_PRIMARY },
  metricLab: { fontSize: 9, color: '#94A3B8', fontWeight: '800', marginTop: 3, textTransform: 'uppercase' },
  cashEstimate: { fontSize: 10, color: SUCCESS, fontWeight: 'bold', marginTop: 4 },
  metricSubNote: { fontSize: 9, color: '#CBD5E1', fontWeight: 'bold', marginTop: 4 },

  section: { marginBottom: 30 },
  sectionHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 15 },
  sectionTitle: { fontSize: 16, fontWeight: 'bold', color: BLUE_PRIMARY },
  realtimeBadge: { flexDirection: 'row', alignItems: 'center', backgroundColor: '#FEF2F2', paddingHorizontal: 8, paddingVertical: 4, borderRadius: 10, gap: 5 },
  liveDot: { width: 6, height: 6, borderRadius: 3, backgroundColor: '#EF4444' },
  realtimeText: { fontSize: 8, fontWeight: '900', color: '#EF4444' },

  listContainer: { backgroundColor: WHITE, borderRadius: 24, paddingHorizontal: 5, paddingVertical: 10, elevation: 3, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 10 },
  listItem: { flexDirection: 'row', alignItems: 'center', padding: 15, borderBottomWidth: 1, borderBottomColor: '#F1F5F9' },
  listIconBox: { width: 44, height: 44, borderRadius: 14, backgroundColor: '#F1F5F9', justifyContent: 'center', alignItems: 'center' },
  listBody: { flex: 1, marginLeft: 15 },
  listTitle: { fontSize: 14, fontWeight: 'bold', color: BLUE_PRIMARY },
  listSub: { fontSize: 11, color: '#94A3B8', marginTop: 2 },
  listTail: { alignItems: 'flex-end' },
  listAmount: { fontSize: 14, fontWeight: 'bold' },

  emptyContainer: { alignItems: 'center', paddingVertical: 40, paddingHorizontal: 20 },
  emptyTitle: { fontSize: 15, fontWeight: 'bold', color: BLUE_PRIMARY, marginTop: 15 },
  emptyText: { fontSize: 11, color: '#94A3B8', textAlign: 'center', marginTop: 5, lineHeight: 16 },

  bottomNav: { position: 'absolute', bottom: 0, left: 0, right: 0, height: 90, backgroundColor: WHITE, flexDirection: 'row', paddingHorizontal: 20, paddingBottom: 20, borderTopLeftRadius: 35, borderTopRightRadius: 35, elevation: 50, alignItems: 'center' },
  navItem: { flex: 1, alignItems: 'center', justifyContent: 'center' },
  navLabel: { fontSize: 10, fontWeight: 'bold', color: '#94A3B8', marginTop: 4 },
});
