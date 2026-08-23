import 'package:carousel_slider/carousel_slider.dart';
import 'package:pusher_beams/pusher_beams.dart';
import 'package:qrpay/backend/local_storage/local_storage.dart';
import 'package:qrpay/backend/model/pusher/pusher_beams_model.dart';
import 'package:qrpay/backend/services/api_endpoint.dart';
import 'package:qrpay/backend/services/pusher/pusher_api_services.dart';
import 'package:qrpay/utils/basic_screen_imports.dart';

import '../../backend/model/bottom_navbar_model/dashboard_model.dart';
import '../../backend/services/api_services.dart';
import '../../custom_assets/assets.gen.dart';
import '../../model/categories_model.dart';
import '../../routes/routes.dart';
import '../app_settings/app_settings_controller.dart';

class DashBoardController extends GetxController {
  List<CategoriesModel> categoriesData = [];
  final CarouselSliderController carouselController =
      CarouselSliderController();
  RxInt current = 0.obs;

  RxDouble percentCharge = 0.0.obs;
  RxDouble fixedCharge = 0.0.obs;
  RxDouble rate = 0.0.obs;
  RxDouble limitMin = 0.0.obs;
  RxDouble limitMax = 0.0.obs;

  RxBool isFirst = true.obs;
  RxBool isLoggedIn = true.obs;
  Stream<DashboardModel> getDashboardDataStream() async* {
    while (isLoggedIn.value) {
      await Future.delayed(Duration(seconds: isFirst.value ? 0 : 2));
      if (isLoggedIn.value) {
        DashboardModel data = await getDashboardData();
        Get.find<AppSettingsController>().getSplashAndOnboardData();
        isFirst.value = false;
        yield data;
      }
    }
  }

  final _isLoading = false.obs;

  bool get isLoading => _isLoading.value;
  late DashboardModel _dashboardModel;

  DashboardModel get dashBoardModel => _dashboardModel;

  Future<DashboardModel> getDashboardData() async {
    _isLoading.value = true;

    update();
    // calling  from api service
    await ApiServices.dashboardApi().then((value) async {
      _dashboardModel = value!;
      final data = _dashboardModel.data.moduleAccess;
      if (LocalStorages.isPusherAuthentication() == false) {
        await PusherBeams.instance.start(
          _dashboardModel.data.pusherCredentials.instanceId,
        );
        LocalStorages.savePusherInstanceId(
          key: _dashboardModel.data.pusherCredentials.instanceId,
        );
        getPusherAuth();
      }

      LocalStorages.saveCardType(
        cardName: _dashboardModel.data.activeVirtualSystem ?? '',
      );

      categoriesData.clear();

      if (data.sendMoney) {
        categoriesData.add(CategoriesModel(Assets.icon.send, Strings.send, () {
          Get.toNamed(Routes.moneyTransferScreen);
        }));
      }

      if (data.receiveMoney) {
        categoriesData
            .add(CategoriesModel(Assets.icon.receive, Strings.receive, () {
          Get.toNamed(Routes.moneyReceiveScreen);
        }));
      }

      if (data.remittanceMoney) {
        categoriesData.add(
            CategoriesModel(Assets.icon.remittance, Strings.remittance, () {
          Get.toNamed(Routes.remittanceScreen);
        }));
      }

      if (data.addMoney) {
        categoriesData.add(
          CategoriesModel(Assets.icon.deposit, Strings.addMoney, () {
            Get.toNamed(Routes.addMoneyScreen);
          }),
        );
      }

      if (data.withdrawMoney) {
        categoriesData.add(
          CategoriesModel(Assets.icon.withdraw, Strings.withdraw, () {
            Get.toNamed(Routes.withdrawScreen);
          }),
        );
      }

      if (data.makePayment) {
        categoriesData.add(
          CategoriesModel(Assets.icon.receipt, Strings.makePayment, () {
            Get.toNamed(Routes.makePaymentScreen);
          }),
        );
      }

      if (data.virtualCard) {
        categoriesData.add(
          CategoriesModel(
            Assets.icon.virtualCard,
            Strings.virtualCard,
            () {
              Get.toNamed(Routes.virtualCardScreen);
            },
          ),
        );
      }

      if (data.billPay) {
        categoriesData
            .add(CategoriesModel(Assets.icon.billPay, Strings.billPay, () {
          Get.toNamed(Routes.billPayScreen);
        }));
      }

      if (data.mobileTopUp) {
        categoriesData.add(
          CategoriesModel(Assets.icon.mobileTopUp, Strings.mobileTopUp, () {
            Get.toNamed(Routes.mobileToUpScreen);
          }),
        );
      }

      if (data.payLink) {
        categoriesData.add(
          CategoriesModel(Assets.icon.paylink, Strings.payLink, () {
            Get.toNamed(Routes.paymentLogScreen);
          }),
        );
      }

      if (data.requestMoney) {
        categoriesData.add(
          CategoriesModel(Assets.icon.requestMoney2, Strings.requestMoney, () {
            Get.toNamed(Routes.requestMoneyScreen);
          }),
        );
      }

      if (data.requestMoney) {
        categoriesData.add(
          CategoriesModel(Assets.icon.agent, Strings.agentMoneyOut, () {
            Get.toNamed(Routes.agentMoneyOutScreen);
          }),
        );
      }
      _isLoading.value = false;
      limitMin.value = _dashboardModel.data.cardReloadCharge.minLimit;
      limitMax.value = _dashboardModel.data.cardReloadCharge.maxLimit;
      percentCharge.value = _dashboardModel.data.cardReloadCharge.percentCharge;
      fixedCharge.value = _dashboardModel.data.cardReloadCharge.fixedCharge;
      _isLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    update();
    return _dashboardModel;
  }

  // Register Pusher Beams
  final _isPusherBeamsLoading = false.obs;
  bool get isPusherBeamsLoading => _isPusherBeamsLoading.value;
  late PusherBeamsModel _pusherBeamsModel;

  PusherBeamsModel get pusherBeamsModel => _pusherBeamsModel;

  Future<PusherBeamsModel> getPusherAuth() async {
    _isPusherBeamsLoading.value = true;

    update();
    await PusherApiServices.getPusherBeamsAuth(LocalStorages.getId()!)
        .then((value) {
      _pusherBeamsModel = value!;
      getSecure();
      LocalStorages.savePusherAuthenticationKey(pusherAuthenticationKey: true);
      _isPusherBeamsLoading.value = false;
      update();
    }).catchError((onError) {
      log.e(onError);
    });
    update();
    return _pusherBeamsModel;
  }

  getSecure() async {
    final BeamsAuthProvider provider = BeamsAuthProvider()
      ..authUrl = ApiEndpoint.pusherBeamsAuthMain
      ..headers = {'Content-Type': 'application/json'}
      ..queryParams = {'user_id': LocalStorages.getId() ?? ""}
      ..credentials = 'omit';

    await PusherBeams.instance.setUserId(
      LocalStorages.getId() ?? "",
      provider,
      (error) => {
        if (error != null) {},
      },
    );
  }
}
