import 'package:qrpay/views/navbar/dashboard_screen.dart';
import 'package:qrpay/views/navbar/notification_screen.dart';
import 'package:get/get.dart';

class NavbarController extends GetxController {
  final RxInt selectedIndex = 0.obs;
  final List page = [DashboardScreen(), NotificationScreen()];

  void selectedPage(int index) {
    selectedIndex.value = index;
  }
}
