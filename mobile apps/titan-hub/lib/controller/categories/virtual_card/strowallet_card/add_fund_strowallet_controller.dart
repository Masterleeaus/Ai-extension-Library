import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:qrpay/backend/services/%20strowallet_api_services.dart';

import '../../../../backend/model/categories/virtual_card/strowallet_models/charge_strowallet_model.dart';
import '../../../../backend/model/common/common_success_model.dart';
import '../../../../backend/services/api_services.dart';
import '../../../../language/english.dart';
import '../../../../routes/routes.dart';
import '../../../../utils/custom_color.dart';
import '../../../../utils/custom_style.dart';
import '../../../../utils/dimensions.dart';
import '../../../../widgets/others/congratulation_widget.dart';
import '../../../../widgets/text_labels/custom_title_heading_widget.dart';
import 'strowallelt_info_controller.dart';

class StrowalletAddFundController extends GetxController {
  final amountTextController = TextEditingController();
  List<String> totalAmount = [];

  RxString selectCurrency = 'USD'.obs;
  RxString selectWallet = 'Paypal'.obs;

  final controller = Get.put(VirtualStrowalletCardController());

  @override
  void dispose() {
    amountTextController.dispose();

    super.dispose();
  }

  @override
  void onInit() {
    getCardChargesData();
    super.onInit();
  }

  goToAddMoneyPreviewScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  goToAddMoneyCongratulationScreen() {
    Get.toNamed(Routes.addFundPreviewScreen);
  }

  RxString selectItem = ''.obs;
  List<String> keyboardItemList = [
    '1',
    '2',
    '3',
    '4',
    '5',
    '6',
    '7',
    '8',
    '9',
    '.',
    '0',
    '<'
  ];
  List currencyList = ['USD', 'BDT'];
  List gatewayList = ['Paypal', 'Stripe'];

  inputItem(int index) {
    return InkWell(
      overlayColor: WidgetStateProperty.all(Colors.transparent),
      onLongPress: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.clear();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        }
      },
      onTap: () {
        if (index == 11) {
          if (totalAmount.isNotEmpty) {
            totalAmount.removeLast();
            amountTextController.text = totalAmount.join('');
          } else {
            return;
          }
        } else {
          if (totalAmount.contains('.') && index == 9) {
            return;
          } else {
            totalAmount.add(keyboardItemList[index]);
            amountTextController.text = totalAmount.join('');
            debugPrint(totalAmount.join(''));
          }
        }
      },
      child: Center(
        child: CustomTitleHeadingWidget(
          text: keyboardItemList[index],
          style: Get.isDarkMode
              ? CustomStyle.lightHeading2TextStyle.copyWith(
                  fontSize: Dimensions.headingTextSize3 * 2,
                )
              : CustomStyle.darkHeading2TextStyle.copyWith(
                  color: CustomColor.primaryTextColor,
                  fontSize: Dimensions.headingTextSize3 * 2,
                ),
        ),
      ),
    );
  }

  final _isLoading = false.obs;
  bool get isLoading => _isLoading.value;

  // add fund  process
  late CommonSuccessModel _withdrawModel;

  CommonSuccessModel get withdrawModel => _withdrawModel;

  Future<CommonSuccessModel> addFundProcess(context) async {
    _isLoading.value = true;
    update();

    Map<String, dynamic> inputBody = {
      'card_id': controller.strowalletCardId.value,
      'fund_amount': int.parse(amountTextController.text.trim()),
    };
    await StrowalletApiServices.strowalletCardFundApi(body: inputBody)
        .then((value) {
      _withdrawModel = value!;

      update();
      StatusScreen.show(
        context: context,
        subTitle: Strings.addMoneySuccessfully.tr,
        onPressed: () {
          Get.offAllNamed(Routes.bottomNavBarScreen);
        },
      );
    }).catchError((onError) {
      log.e(onError);
      _isLoading.value = false;
    });

    _isLoading.value = false;
    update();
    return _withdrawModel;
  }

//
  final _isLoadingCharge = false.obs;
  bool get isLoadingCharge => _isLoadingCharge.value;

  // card charges info
  late StrowalletChargeModel _cardChargesModel;
  StrowalletChargeModel get cardChargesModel => _cardChargesModel;

  Future<StrowalletChargeModel> getCardChargesData() async {
    _isLoadingCharge.value = true;
    update();

    await StrowalletApiServices.strollerCardChargesApi().then((value) {
      _cardChargesModel = value!;
      update();
    }).catchError((onError) {
      log.e(onError);
      _isLoadingCharge.value = false;
      update();
    });

    _isLoadingCharge.value = false;
    update();
    return _cardChargesModel;
  }
}
